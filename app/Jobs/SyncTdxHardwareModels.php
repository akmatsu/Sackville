<?php

namespace App\Jobs;

use App\Enums\ObjectCodeCategory;
use App\Enums\SyncRunStatus;
use App\Enums\TdxAssetSource;
use App\Models\SyncRun;
use App\Models\TdxAsset;
use App\Support\Tdx\TdxClient;
use App\Support\Tdx\TdxRowResolver;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncTdxHardwareModels implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * TDX custom attribute IDs on report 362 (workstations). These are not
     * documented anywhere else, so keep the mapping in one place.
     */
    private const ATTR_FUNDING = 2110;

    private const ATTR_FY_REPLACEMENT = 2111;

    private const ATTR_WARRANTY_END = 2113;

    private const ATTR_DESCRIPTION = 2114;

    // TODO: confirm with Freedom / Brooke / Katie whether TDX tracks docking
    // station status anywhere (not present in report 362 as of 2026-08-12).
    // Until then, has_docking_station is left unset by sync and can only be
    // populated manually.

    /**
     * Object/sub-object code for workstation replacements, confirmed with
     * Katie/Brooke. Every workstation is coded here regardless of which GL
     * fund/department/division it's funded under.
     */
    private const WORKSTATION_OBJECT_CODE = '434';

    private const WORKSTATION_SUB_OBJECT_CODE = '000';

    /**
     * Keep duplicate runs off the queue while one is already processing or
     * scheduled — the scheduler and manual "sync now" trigger can otherwise
     * both dispatch within the same window.
     */
    public int $uniqueFor = 3600;

    public function __construct(public readonly ?int $syncRunId = null)
    {
        //
    }

    public function uniqueId(): string
    {
        return 'tdx-hardware-models-sync';
    }

    public function handle(): void
    {
        $syncRun = $this->syncRunId !== null
            ? SyncRun::findOrFail($this->syncRunId)
            : SyncRun::create([
                'integration' => 'tdx',
                'started_at' => now(),
                'status' => SyncRunStatus::Running,
            ]);

        try {
            $result = $this->sync();

            $syncRun->update([
                'finished_at' => now(),
                'records_synced' => $result['synced'],
                'records_failed' => $result['failed'],
                'status' => match (true) {
                    $result['failed'] === 0 => SyncRunStatus::Success,
                    $result['synced'] > 0 => SyncRunStatus::Partial,
                    default => SyncRunStatus::Failed,
                },
                'errors' => $result['errors'] ?: null,
            ]);
        } catch (Throwable $e) {
            $syncRun->update([
                'finished_at' => now(),
                'records_synced' => 0,
                'records_failed' => 0,
                'status' => SyncRunStatus::Failed,
                'errors' => ['message' => $e->getMessage()],
            ]);

            Log::error('TDX hardware model sync failed.', ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Reconcile TDX workstation rows into `tdx_assets`, upserting by
     * `AssetID`. Writes are chunked (and each chunk committed in its own
     * transaction) so a single bad chunk can't roll back everything already
     * saved. TDX itself returns the full row set in one HTTP call — there's
     * no read-side pagination to do.
     *
     * @return array{synced: int, failed: int, errors: list<array{asset_id: mixed, message: string}>}
     */
    protected function sync(): array
    {
        $client = new TdxClient;

        $token = $client->authenticate();
        $workstations = $client->getWorkstations($token);

        if (empty($workstations)) {
            Log::warning('TDX returned no workstation rows; skipping surplus marking to avoid flagging every asset as surplus.');

            return ['synced' => 0, 'failed' => 0, 'errors' => []];
        }

        $seenAssetIds = [];
        $synced = 0;
        $failed = 0;
        $errors = [];
        $resolver = new TdxRowResolver;

        foreach (collect($workstations)->chunk(100) as $chunk) {
            DB::transaction(function () use ($chunk, $resolver, &$seenAssetIds, &$synced, &$failed, &$errors): void {
                foreach ($chunk as $row) {
                    try {
                        $seenAssetIds[] = $this->syncRow($row, $resolver);
                        $synced++;
                    } catch (Throwable $e) {
                        $failed++;
                        $errors[] = [
                            'asset_id' => $row['AssetID'] ?? null,
                            'message' => $e->getMessage(),
                        ];

                        Log::warning('Skipped a TDX workstation row during hardware sync.', [
                            'asset_id' => $row['AssetID'] ?? null,
                            'exception' => $e,
                        ]);
                    }
                }
            });
        }

        TdxAsset::where('source', TdxAssetSource::Workstation)
            ->whereNotIn('tdx_asset_id', $seenAssetIds)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Surplus'))
            ->update(['status' => 'Surplus']);

        return ['synced' => $synced, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    protected function syncRow(array $row, TdxRowResolver $resolver): string
    {
        if (! isset($row['AssetID'])) {
            throw new RuntimeException('TDX row is missing an AssetID.');
        }

        $assetId = (string) $row['AssetID'];
        $funding = $resolver->parseFundingField($row[self::ATTR_FUNDING] ?? null);
        $responsibleOrg = $resolver->resolveResponsibleOrg($funding);

        TdxAsset::updateOrCreate(
            ['tdx_asset_id' => $assetId],
            [
                'source' => TdxAssetSource::Workstation,
                'status' => $resolver->stringOrNull($row['StatusName'] ?? null),
                'description' => $resolver->stringOrNull($row[self::ATTR_DESCRIPTION] ?? null),
                'asset_tag' => $resolver->stringOrNull($row['Tag'] ?? null),
                'serial' => $resolver->stringOrNull($row['SerialNumber'] ?? null),
                'hardware_model_id' => $resolver->resolveHardwareModel($row, 'Workstation')?->id,
                'assigned_user_upn' => $resolver->stringOrNull($row['OwningCustomerName'] ?? null),
                'assigned_department_code' => $responsibleOrg['department_code'],
                'responsible_division_id' => $responsibleOrg['responsible_division_id'],
                'responsible_location_id' => $responsibleOrg['responsible_location_id'],
                'gl_code_id' => $funding !== null
                    ? $resolver->resolveGlCode(
                        $funding['fund_code'],
                        $funding['department_code'],
                        $funding['division_code'],
                        self::WORKSTATION_OBJECT_CODE,
                        'Equipment',
                        ObjectCodeCategory::Equipment,
                        self::WORKSTATION_SUB_OBJECT_CODE,
                        'IT Equipment under $25,000',
                    )->id
                    : null,
                'fy_replacement' => $resolver->parseFyReplacement($row[self::ATTR_FY_REPLACEMENT] ?? null),
                'warranty_ends_at' => $this->parseWarrantyEndsAt($row[self::ATTR_WARRANTY_END] ?? null),
                'last_synced_at' => now(),
                'raw_payload' => $row,
            ]
        );

        return $assetId;
    }

    protected function parseWarrantyEndsAt(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            Log::warning('Could not parse TDX warranty end date.', ['value' => $value]);

            return null;
        }
    }
}
