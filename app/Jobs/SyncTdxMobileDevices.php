<?php

namespace App\Jobs;

use App\Enums\ObjectCodeCategory;
use App\Enums\SyncRunStatus;
use App\Enums\TdxAssetSource;
use App\Models\SyncRun;
use App\Models\TdxAsset;
use App\Models\TdxMobilePlan;
use App\Support\Tdx\TdxClient;
use App\Support\Tdx\TdxRowResolver;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyncTdxMobileDevices implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * TDX custom attribute IDs on report 363 (mobile). Funding (2110), FY
     * replacement (2111), and description (2114) are the same attribute IDs
     * report 362 (workstations) uses. 2112/2389/2390 are plan-only.
     */
    private const ATTR_FUNDING = 2110;

    private const ATTR_FY_REPLACEMENT = 2111;

    private const ATTR_DESCRIPTION = 2114;

    private const ATTR_PO_NUMBER = 2112;

    private const ATTR_PLAN_STATUS = 2389;

    private const ATTR_PLAN_DESCRIPTION = 2390;

    /**
     * Object/sub-object code for mobile devices — same as workstations,
     * confirmed with Freedom.
     */
    private const DEVICE_OBJECT_CODE = '434';

    private const DEVICE_SUB_OBJECT_CODE = '000';

    /**
     * Object/sub-object code for mobile (cellular) plans, confirmed with
     * Freedom. The display name/category below are a guess — AGENTS.md only
     * documents 421.1 generally as "communication/network" — so treat the
     * name/category as unconfirmed pending a check with Finance, even though
     * the code itself (421.100) is confirmed.
     */
    private const PLAN_OBJECT_CODE = '421';

    private const PLAN_SUB_OBJECT_CODE = '100';

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
        return 'tdx-mobile-devices-sync';
    }

    public function handle(): void
    {
        $syncRun = $this->syncRunId !== null
            ? SyncRun::findOrFail($this->syncRunId)
            : SyncRun::create([
                'integration' => 'tdx-mobile',
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

            Log::error('TDX mobile device sync failed.', ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Reconciles TDX report 363 rows into `tdx_assets` (mobile devices,
     * `source = mobile`) and `tdx_mobile_plans` (cellular lines/plans),
     * upserting by `AssetID`. Writes are chunked (and each chunk committed
     * in its own transaction) so a single bad chunk can't roll back
     * everything already saved.
     *
     * Surplus marking is swept separately per table: `tdx_assets` is scoped
     * to `source = mobile` so this doesn't touch workstation rows written by
     * the separate hardware sync; `tdx_mobile_plans` needs no scoping since
     * only this job ever writes to it.
     *
     * @return array{synced: int, failed: int, errors: list<array{asset_id: mixed, message: string}>}
     */
    protected function sync(): array
    {
        $client = new TdxClient;

        $token = $client->authenticate();
        $rows = $client->getMobileDevices($token);

        if (empty($rows)) {
            Log::warning('TDX returned no mobile rows; skipping surplus marking to avoid flagging every asset/plan as surplus.');

            return ['synced' => 0, 'failed' => 0, 'errors' => []];
        }

        $seenDeviceAssetIds = [];
        $seenPlanAssetIds = [];
        $synced = 0;
        $failed = 0;
        $errors = [];
        $resolver = new TdxRowResolver;

        foreach (collect($rows)->chunk(100) as $chunk) {
            DB::transaction(function () use ($chunk, $resolver, &$seenDeviceAssetIds, &$seenPlanAssetIds, &$synced, &$failed, &$errors): void {
                foreach ($chunk as $row) {
                    try {
                        $assetId = $this->syncRow($row, $resolver);

                        if ($this->isPlanRow($row)) {
                            $seenPlanAssetIds[] = $assetId;
                        } else {
                            $seenDeviceAssetIds[] = $assetId;
                        }

                        $synced++;
                    } catch (Throwable $e) {
                        $failed++;
                        $errors[] = [
                            'asset_id' => $row['AssetID'] ?? null,
                            'message' => $e->getMessage(),
                        ];

                        Log::warning('Skipped a TDX mobile row during sync.', [
                            'asset_id' => $row['AssetID'] ?? null,
                            'exception' => $e,
                        ]);
                    }
                }
            });
        }

        TdxAsset::where('source', TdxAssetSource::Mobile)
            ->whereNotIn('tdx_asset_id', $seenDeviceAssetIds)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Surplus'))
            ->update(['status' => 'Surplus']);

        TdxMobilePlan::whereNotIn('tdx_asset_id', $seenPlanAssetIds)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Surplus'))
            ->update(['status' => 'Surplus']);

        return ['synced' => $synced, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    protected function isPlanRow(array $row): bool
    {
        return strcasecmp((string) ($row['ProductTypeName'] ?? ''), 'Plan') === 0;
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    protected function syncRow(array $row, TdxRowResolver $resolver): string
    {
        if (! isset($row['AssetID'])) {
            throw new RuntimeException('TDX row is missing an AssetID.');
        }

        return $this->isPlanRow($row)
            ? $this->syncPlanRow($row, $resolver)
            : $this->syncDeviceRow($row, $resolver);
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    protected function syncPlanRow(array $row, TdxRowResolver $resolver): string
    {
        $assetId = (string) $row['AssetID'];
        $funding = $resolver->parseFundingField($row[self::ATTR_FUNDING] ?? null);
        $responsibleOrg = $resolver->resolveResponsibleOrg($funding);

        TdxMobilePlan::updateOrCreate(
            ['tdx_asset_id' => $assetId],
            [
                'status' => $resolver->stringOrNull($row['StatusName'] ?? null),
                'carrier' => $resolver->stringOrNull($row['ManufacturerName'] ?? null),
                'po_number' => $resolver->stringOrNull($row[self::ATTR_PO_NUMBER] ?? null),
                'plan_status' => $resolver->stringOrNull($row[self::ATTR_PLAN_STATUS] ?? null),
                'plan_description' => $resolver->stringOrNull($row[self::ATTR_PLAN_DESCRIPTION] ?? null),
                'description' => $resolver->stringOrNull($row[self::ATTR_DESCRIPTION] ?? null),
                'asset_tag' => $resolver->stringOrNull($row['Tag'] ?? null),
                'serial' => $resolver->stringOrNull($row['SerialNumber'] ?? null),
                'assigned_user_upn' => $resolver->stringOrNull($row['OwningCustomerName'] ?? null),
                'assigned_department_code' => $responsibleOrg['department_code'],
                'responsible_division_id' => $responsibleOrg['responsible_division_id'],
                'responsible_location_id' => $responsibleOrg['responsible_location_id'],
                'gl_code_id' => $funding !== null
                    ? $resolver->resolveGlCode(
                        $funding['fund_code'],
                        $funding['department_code'],
                        $funding['division_code'],
                        self::PLAN_OBJECT_CODE,
                        'Communications',
                        ObjectCodeCategory::Contractual,
                        self::PLAN_SUB_OBJECT_CODE,
                        'Mobile Service Plans',
                    )->id
                    : null,
                'last_synced_at' => now(),
                'raw_payload' => $row,
            ]
        );

        return $assetId;
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    protected function syncDeviceRow(array $row, TdxRowResolver $resolver): string
    {
        $assetId = (string) $row['AssetID'];
        $funding = $resolver->parseFundingField($row[self::ATTR_FUNDING] ?? null);
        $responsibleOrg = $resolver->resolveResponsibleOrg($funding);

        TdxAsset::updateOrCreate(
            ['tdx_asset_id' => $assetId],
            [
                'source' => TdxAssetSource::Mobile,
                'status' => $resolver->stringOrNull($row['StatusName'] ?? null),
                'product_type' => $resolver->stringOrNull($row['ProductTypeName'] ?? null),
                'description' => $resolver->stringOrNull($row[self::ATTR_DESCRIPTION] ?? null),
                'asset_tag' => $resolver->stringOrNull($row['Tag'] ?? null),
                'serial' => $resolver->stringOrNull($row['SerialNumber'] ?? null),
                'plan_serial' => $resolver->stringOrNull($row['ParentSerial'] ?? null),
                'hardware_model_id' => $resolver->resolveHardwareModel($row, 'Mobile')?->id,
                'assigned_user_upn' => $resolver->stringOrNull($row['OwningCustomerName'] ?? null),
                'assigned_department_code' => $responsibleOrg['department_code'],
                'responsible_division_id' => $responsibleOrg['responsible_division_id'],
                'responsible_location_id' => $responsibleOrg['responsible_location_id'],
                'gl_code_id' => $funding !== null
                    ? $resolver->resolveGlCode(
                        $funding['fund_code'],
                        $funding['department_code'],
                        $funding['division_code'],
                        self::DEVICE_OBJECT_CODE,
                        'Equipment',
                        ObjectCodeCategory::Equipment,
                        self::DEVICE_SUB_OBJECT_CODE,
                        'IT Equipment under $25,000',
                    )->id
                    : null,
                'fy_replacement' => $resolver->parseFyReplacement($row[self::ATTR_FY_REPLACEMENT] ?? null),
                'last_synced_at' => now(),
                'raw_payload' => $row,
            ]
        );

        return $assetId;
    }
}
