<?php

namespace App\Jobs;

use App\Enums\ObjectCodeCategory;
use App\Enums\SyncRunStatus;
use App\Models\SyncRun;
use App\Models\TdxPublicWifiCircuit;
use App\Support\FiscalYear;
use App\Support\Tdx\TdxClient;
use App\Support\Tdx\TdxRowResolver;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncTdxPublicWifi implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Object/sub-object code for public wifi circuits — generic
     * communication/network services, confirmed with Freedom. Matches the
     * code the mobile cellular plan sync already resolves to.
     */
    private const CIRCUIT_OBJECT_CODE = '421';

    private const CIRCUIT_SUB_OBJECT_CODE = '100';

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
        return 'tdx-public-wifi-sync';
    }

    public function handle(): void
    {
        $syncRun = $this->syncRunId !== null
            ? SyncRun::findOrFail($this->syncRunId)
            : SyncRun::create([
                'integration' => 'tdx-public-wifi',
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

            Log::error('TDX public wifi sync failed.', ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Reconciles TDX report 985 rows into `tdx_public_wifi_circuits`,
     * upserting by `AssetID`. Writes are chunked (and each chunk committed in
     * its own transaction) so a single bad chunk can't roll back everything
     * already saved.
     *
     * @return array{synced: int, failed: int, errors: list<array{asset_id: mixed, message: string}>}
     */
    protected function sync(): array
    {
        $client = new TdxClient;

        $token = $client->authenticate();
        $rows = $client->getPublicWifi($token);

        if (empty($rows)) {
            Log::warning('TDX returned no public wifi rows; skipping surplus marking to avoid flagging every circuit as surplus.');

            return ['synced' => 0, 'failed' => 0, 'errors' => []];
        }

        $seenAssetIds = [];
        $synced = 0;
        $failed = 0;
        $errors = [];
        $resolver = new TdxRowResolver;

        foreach (collect($rows)->chunk(100) as $chunk) {
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

                        Log::warning('Skipped a TDX public wifi row during sync.', [
                            'asset_id' => $row['AssetID'] ?? null,
                            'exception' => $e,
                        ]);
                    }
                }
            });
        }

        TdxPublicWifiCircuit::whereNotIn('tdx_asset_id', $seenAssetIds)
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
        $funding = $resolver->parseFundingField($row[2673] ?? null);
        $responsibleOrg = $resolver->resolveResponsibleOrg($funding);
        $monthlyCost = $row[2675] ?? null;

        $circuit = TdxPublicWifiCircuit::updateOrCreate(
            ['tdx_asset_id' => $assetId],
            [
                'status' => $resolver->stringOrNull($row['StatusName'] ?? null),
                'location_name' => $resolver->stringOrNull($row['LocationName'] ?? null),
                'address' => $resolver->stringOrNull($row['ITAMAddress'] ?? null),
                'speed' => $resolver->stringOrNull($row[2712] ?? null),
                'po_number' => $resolver->stringOrNull($row[2674] ?? null),
                'notes' => $resolver->stringOrNull($row[2713] ?? null),
                'assigned_department_code' => $responsibleOrg['department_code'],
                'responsible_division_id' => $responsibleOrg['responsible_division_id'],
                'responsible_location_id' => $responsibleOrg['responsible_location_id'],
                'gl_code_id' => $funding !== null
                    ? $resolver->resolveGlCode(
                        $funding['fund_code'],
                        $funding['department_code'],
                        $funding['division_code'],
                        self::CIRCUIT_OBJECT_CODE,
                        'Communications',
                        ObjectCodeCategory::Contractual,
                        self::CIRCUIT_SUB_OBJECT_CODE,
                        'Communication Network Services',
                    )->id
                    : null,
                'last_synced_at' => now(),
                'raw_payload' => $row,
            ]
        );

        $circuit->costs()->updateOrCreate(
            ['fiscal_year' => FiscalYear::current()],
            [
                'monthly_cost' => $monthlyCost,
                'yearly_cost' => is_numeric($monthlyCost) ? round((float) $monthlyCost * 12, 2) : null,
                'purchase_cost' => $row['PurchaseCost'] ?? null,
            ]
        );

        return $assetId;
    }
}
