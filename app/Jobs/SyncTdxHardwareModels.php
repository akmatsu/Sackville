<?php

namespace App\Jobs;

use App\Enums\FundType;
use App\Enums\ObjectCodeCategory;
use App\Enums\SyncRunStatus;
use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\ObjectCode;
use App\Models\SubObjectCode;
use App\Models\SyncRun;
use App\Models\TdxAsset;
use App\Models\Vendor;
use App\Support\Tdx\TdxClient;
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

    /**
     * Object/sub-object code for workstation replacements, confirmed with
     * Katie/Brooke. Every workstation is coded here regardless of which GL
     * fund/department/division it's funded under.
     */
    private const WORKSTATION_OBJECT_CODE = '434';

    private const WORKSTATION_SUB_OBJECT_CODE = '000';

    /**
     * Department codes and names documented in AGENTS.md. Used to give
     * auto-created department rows a real name (and mark them active)
     * instead of a placeholder, when the GL segment matches one of these.
     *
     * @var array<string, string>
     */
    private const KNOWN_DEPARTMENT_NAMES = [
        '100' => 'Administration',
        '110' => 'Administration',
        '115' => 'Information Technology',
        '120' => 'Finance',
        '130' => 'Planning',
        '150' => 'Public Works',
        '160' => 'Emergency Services',
        '170' => 'Community Development',
    ];

    /**
     * Keep duplicate runs off the queue while one is already processing or
     * scheduled — the scheduler and manual "sync now" trigger can otherwise
     * both dispatch within the same window.
     */
    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'tdx-hardware-models-sync';
    }

    public function handle(): void
    {
        $startedAt = now();

        try {
            $result = $this->sync();

            SyncRun::create([
                'integration' => 'tdx',
                'started_at' => $startedAt,
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
            SyncRun::create([
                'integration' => 'tdx',
                'started_at' => $startedAt,
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

        foreach (collect($workstations)->chunk(100) as $chunk) {
            DB::transaction(function () use ($chunk, &$seenAssetIds, &$synced, &$failed, &$errors): void {
                foreach ($chunk as $row) {
                    try {
                        $seenAssetIds[] = $this->syncRow($row);
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

        TdxAsset::whereNotIn('tdx_asset_id', $seenAssetIds)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'Surplus'))
            ->update(['status' => 'Surplus']);

        return ['synced' => $synced, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    protected function syncRow(array $row): string
    {
        if (! isset($row['AssetID'])) {
            throw new RuntimeException('TDX row is missing an AssetID.');
        }

        $assetId = (string) $row['AssetID'];
        $funding = $this->parseFundingField($row[self::ATTR_FUNDING] ?? null);
        $responsibleOrg = $this->resolveResponsibleOrg($funding);

        TdxAsset::updateOrCreate(
            ['tdx_asset_id' => $assetId],
            [
                'status' => $this->stringOrNull($row['StatusName'] ?? null),
                'description' => $this->stringOrNull($row[self::ATTR_DESCRIPTION] ?? null),
                'asset_tag' => $this->stringOrNull($row['Tag'] ?? null),
                'serial' => $this->stringOrNull($row['SerialNumber'] ?? null),
                'hardware_model_id' => $this->resolveHardwareModel($row)?->id,
                'assigned_user_upn' => $this->stringOrNull($row['OwningCustomerName'] ?? null),
                'assigned_department_code' => $responsibleOrg['department_code'],
                'assigned_division_id' => $responsibleOrg['division_id'],
                'assigned_location_name' => $responsibleOrg['location_name'],
                'gl_code_id' => $funding !== null
                    ? $this->resolveGlCode($funding['fund_code'], $funding['department_code'], $funding['division_code'])->id
                    : null,
                'fy_replacement' => $this->parseFyReplacement($row[self::ATTR_FY_REPLACEMENT] ?? null),
                'warranty_ends_at' => $this->parseWarrantyEndsAt($row[self::ATTR_WARRANTY_END] ?? null),
                'last_synced_at' => now(),
                'raw_payload' => $row,
            ]
        );

        return $assetId;
    }

    /**
     * Parses TDX's "IT Funding" field, e.g.:
     *   "Public Works - Project Mgmt -  100.115.122"
     *   "Community Development - Library - Willow -  200.170.507"
     *
     * Format: <responsible department> - <responsible division> -
     * [<optional third level>] - <fund>.<department>.<division> GL segment.
     * The responsible org (who this is for) and the GL segment (what it's
     * coded to) are two independent hierarchies — confirmed with Katie and
     * Brooke, since IT funds hardware for every department.
     *
     * @return array{department_name: ?string, division_name: ?string, location_name: ?string, fund_code: string, department_code: string, division_code: string}|null
     */
    protected function parseFundingField(mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $parts = array_values(array_filter(
            array_map('trim', preg_split('/\s*-\s*/', trim($value))),
            static fn (string $part): bool => $part !== '',
        ));

        if (count($parts) < 3) {
            Log::warning('Could not parse the TDX IT Funding field; too few segments.', ['value' => $value]);

            return null;
        }

        $glSegment = array_pop($parts);
        $glCodes = explode('.', $glSegment);

        if (count($glCodes) !== 3) {
            Log::warning('Could not parse the GL segment of the TDX IT Funding field.', ['value' => $value]);

            return null;
        }

        [$fundCode, $departmentCode, $divisionCode] = array_map('trim', $glCodes);

        return [
            'department_name' => $parts[0] ?? null,
            'division_name' => $parts[1] ?? null,
            'location_name' => count($parts) > 2 ? implode(' - ', array_slice($parts, 2)) : null,
            'fund_code' => $fundCode,
            'department_code' => $departmentCode,
            'division_code' => $divisionCode,
        ];
    }

    /**
     * Resolves the responsible department/division named in the funding
     * field against existing reference data. This is find-only — TDX gives
     * us names, not codes, for this hierarchy, so there's nothing to key an
     * auto-created row on. Unmatched departments fall back to storing the
     * raw name (assigned_department_code has no FK constraint); unmatched
     * divisions are left null (assigned_division_id is a real FK).
     *
     * @param  array{department_name: ?string, division_name: ?string, location_name: ?string}|null  $funding
     * @return array{department_code: ?string, division_id: ?int, location_name: ?string}
     */
    protected function resolveResponsibleOrg(?array $funding): array
    {
        if ($funding === null) {
            return ['department_code' => null, 'division_id' => null, 'location_name' => null];
        }

        $departmentName = $funding['department_name'];
        $department = $departmentName !== null ? Department::where('name', $departmentName)->first() : null;

        if ($departmentName !== null && $department === null) {
            Log::warning('Could not resolve the responsible department name to a known department; storing the raw name.', [
                'name' => $departmentName,
            ]);
        }

        $divisionName = $funding['division_name'];
        $division = null;

        if ($divisionName !== null && $department !== null) {
            $division = Division::where('name', $divisionName)
                ->where('department_code', $department->code)
                ->first();

            if ($division === null) {
                Log::warning('Could not resolve the responsible division name to a known division; leaving it unset.', [
                    'department_code' => $department->code,
                    'name' => $divisionName,
                ]);
            }
        }

        return [
            'department_code' => $department?->code ?? $departmentName,
            'division_id' => $division?->id,
            'location_name' => $funding['location_name'],
        ];
    }

    /**
     * Resolves (auto-creating if needed, per direction from Katie/Brooke) the
     * gl_codes row for a workstation's fund.department.division.434.000.
     * Auto-created fund/department rows use the known name/type from
     * AGENTS.md when the code is recognized; everything else gets an
     * inactive placeholder so it's obvious in the admin screens that it
     * needs a real name filled in.
     */
    protected function resolveGlCode(string $fundCode, string $departmentCode, string $divisionCode): GlCode
    {
        $fund = Fund::firstOrCreate(
            ['code' => $fundCode],
            [
                'name' => $fundCode === '100' ? 'General Fund' : "Fund {$fundCode} (auto-created)",
                'fund_type' => $this->inferFundType($fundCode),
                'active' => $fundCode === '100',
            ]
        );

        $department = Department::firstOrCreate(
            ['code' => $departmentCode],
            [
                'name' => self::KNOWN_DEPARTMENT_NAMES[$departmentCode] ?? "Department {$departmentCode} (auto-created)",
                'active' => array_key_exists($departmentCode, self::KNOWN_DEPARTMENT_NAMES),
            ]
        );

        $division = Division::firstOrCreate(
            ['code' => $divisionCode],
            [
                'department_code' => $department->code,
                'name' => "Division {$divisionCode} (auto-created)",
                'active' => false,
            ]
        );

        $objectCode = ObjectCode::firstOrCreate(
            ['code' => self::WORKSTATION_OBJECT_CODE],
            [
                'name' => 'Equipment',
                'category' => ObjectCodeCategory::Equipment,
                'active' => true,
            ]
        );

        $subObjectCode = SubObjectCode::firstOrCreate(
            ['object_code' => $objectCode->code, 'code' => self::WORKSTATION_SUB_OBJECT_CODE],
            [
                'name' => 'Workstations',
                'active' => true,
            ]
        );

        return GlCode::firstOrCreate(
            [
                'fund_code' => $fund->code,
                'department_code' => $department->code,
                'division_id' => $division->id,
                'object_code' => $objectCode->code,
                'sub_object_code_id' => $subObjectCode->id,
            ],
            [
                'label' => "{$division->name} — workstations",
                'active' => $division->active,
            ]
        );
    }

    /**
     * Fund code ranges documented in AGENTS.md. Only code 100 has a known
     * name, but the type is inferable for every documented range.
     */
    protected function inferFundType(string $fundCode): FundType
    {
        $code = (int) $fundCode;

        return match (true) {
            $fundCode === '100' => FundType::AreaWide,
            in_array($fundCode, ['200', '202', '203'], true) => FundType::NonAreaWide,
            $code >= 245 && $code <= 259 => FundType::ServiceArea,
            in_array($fundCode, ['265', '293'], true) => FundType::ServiceArea,
            in_array($fundCode, ['510', '520'], true) => FundType::Enterprise,
            default => FundType::NonAreaWide,
        };
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    protected function resolveHardwareModel(array $row): ?HardwareModel
    {
        $manufacturer = $this->stringOrNull($row['ManufacturerName'] ?? null);
        $model = $this->stringOrNull($row['ProductModelName'] ?? null);

        if ($manufacturer === null || $model === null) {
            Log::warning('TDX row is missing a manufacturer or model name; hardware_model_id left null.', [
                'asset_id' => $row['AssetID'] ?? null,
            ]);

            return null;
        }

        $vendor = Vendor::firstOrCreate(['name' => $manufacturer]);
        $category = HardwareCategory::firstOrCreate(['name' => 'Workstation']);

        return HardwareModel::firstOrCreate(
            ['vendor_id' => $vendor->id, 'name' => $model],
            ['hardware_category_id' => $category->id]
        );
    }

    protected function parseFyReplacement(mixed $value): ?int
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits === '' ? null : (int) $digits;
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

    protected function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
