<?php

namespace App\Support\Tdx;

use App\Enums\FundType;
use App\Enums\ObjectCodeCategory;
use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\ObjectCode;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\SubObjectCode;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;

/**
 * Shared row-parsing and GL/org resolution logic for TDX asset sync jobs
 * (workstations, mobile devices, mobile plans). Kept in one place since the
 * funding-field parsing in particular has a documented past bug — see
 * resolveResponsibleOrg() — that duplicating across jobs would risk
 * reintroducing.
 */
final class TdxRowResolver
{
    /**
     * Department codes and names documented in AGENTS.md. Used to give
     * auto-created department rows a real name (and mark them active)
     * instead of a placeholder, when the GL segment matches one of these.
     *
     * Keys are written as numeric strings but PHP coerces them to int keys
     * at array-literal time, hence the int key type below.
     *
     * @var array<int, string>
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
    public function parseFundingField(mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $segments = preg_split('/\s*-\s*/', trim($value));

        if ($segments === false) {
            return null;
        }

        $parts = array_values(array_filter(
            array_map('trim', $segments),
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
     * Resolves the responsible department/division/location named in the
     * funding field. This hierarchy is entirely independent of the GL
     * hierarchy resolved by resolveGlCode() below — confirmed with Katie and
     * Brooke, and confirmed the hard way: an earlier version of this method
     * tried to key the responsible division off the GL segment's division
     * code, which silently mis-attributed assets to unrelated, already-
     * existing GL divisions that happened to share that code. TDX's
     * responsible org labels (e.g. IT's own internal team groupings like
     * "Business Operations") have no relationship to the borough's formal
     * chart-of-accounts departments/divisions tables, so they're resolved
     * against dedicated `responsible_divisions`/`responsible_locations`
     * tables instead, isolated from GL and safe to auto-create into.
     *
     * Department: always the raw name as parsed — no lookup or auto-create
     * against the real `departments` table (that's a GL-hierarchy concept).
     *
     * Division: matched by (department name, division name), case/whitespace
     * -insensitive, within `responsible_divisions`. Auto-created when
     * unmatched — safe, since this table shares no codespace with anything
     * else.
     *
     * Location: only resolved when a division resolved and the funding
     * string's optional third segment is present. Matched/auto-created the
     * same way as division, nested under the resolved division.
     *
     * @param  array{department_name: ?string, division_name: ?string, location_name: ?string}|null  $funding
     * @return array{department_code: ?string, responsible_division_id: ?int, responsible_location_id: ?int}
     */
    public function resolveResponsibleOrg(?array $funding): array
    {
        if ($funding === null) {
            return ['department_code' => null, 'responsible_division_id' => null, 'responsible_location_id' => null];
        }

        $departmentName = $funding['department_name'];
        $divisionName = $funding['division_name'];
        $division = null;

        if ($departmentName !== null && $divisionName !== null) {
            $division = ResponsibleDivision::whereRaw('lower(trim(department_name)) = lower(trim(?))', [$departmentName])
                ->whereRaw('lower(trim(name)) = lower(trim(?))', [$divisionName])
                ->first();

            $division ??= ResponsibleDivision::create([
                'department_name' => $departmentName,
                'name' => $divisionName,
                'active' => true,
            ]);
        }

        $locationName = $funding['location_name'];
        $location = null;

        if ($division !== null && $locationName !== null) {
            $location = ResponsibleLocation::where('responsible_division_id', $division->id)
                ->whereRaw('lower(trim(name)) = lower(trim(?))', [$locationName])
                ->first();

            $location ??= ResponsibleLocation::create([
                'responsible_division_id' => $division->id,
                'name' => $locationName,
                'active' => true,
            ]);
        }

        return [
            'department_code' => $departmentName,
            'responsible_division_id' => $division?->id,
            'responsible_location_id' => $location?->id,
        ];
    }

    /**
     * Resolves (auto-creating if needed, per direction from Katie/Brooke) the
     * gl_codes row for fund.department.division.$objectCode.$subObjectCode.
     * Auto-created fund/department/division rows use the known name/type
     * from AGENTS.md when the code is recognized; everything else gets an
     * inactive placeholder so it's obvious in the admin screens that it
     * needs a real name filled in.
     *
     * The label is built generically from the sub-object name (e.g.
     * "IT — IT Equipment under $25,000") rather than naming which sync wrote
     * it — GlCode::firstOrCreate() matches on
     * (fund, department, division, object, sub-object), so a workstation and
     * a mobile device in the same division both resolving the same object/
     * sub-object code land on the exact same row regardless of which job
     * created it first, and the budget categorizes by code, not by source.
     */
    public function resolveGlCode(
        string $fundCode,
        string $departmentCode,
        string $divisionCode,
        string $objectCode,
        string $objectCodeName,
        ObjectCodeCategory $objectCodeCategory,
        string $subObjectCode,
        string $subObjectCodeName,
    ): GlCode {
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

        $objectCodeModel = ObjectCode::firstOrCreate(
            ['code' => $objectCode],
            [
                'name' => $objectCodeName,
                'category' => $objectCodeCategory,
                'active' => true,
            ]
        );

        $subObjectCodeModel = SubObjectCode::firstOrCreate(
            ['object_code' => $objectCodeModel->code, 'code' => $subObjectCode],
            [
                'name' => $subObjectCodeName,
                'active' => true,
            ]
        );

        return GlCode::firstOrCreate(
            [
                'fund_code' => $fund->code,
                'department_code' => $department->code,
                'division_id' => $division->id,
                'object_code' => $objectCodeModel->code,
                'sub_object_code_id' => $subObjectCodeModel->id,
            ],
            [
                'label' => "{$division->name} — {$subObjectCodeModel->name}",
                'active' => $division->active,
            ]
        );
    }

    /**
     * Fund code ranges documented in AGENTS.md. Only code 100 has a known
     * name, but the type is inferable for every documented range.
     */
    private function inferFundType(string $fundCode): FundType
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
    public function resolveHardwareModel(array $row, string $categoryName): ?HardwareModel
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
        $category = HardwareCategory::firstOrCreate(['name' => $categoryName]);

        return HardwareModel::firstOrCreate(
            ['vendor_id' => $vendor->id, 'name' => $model],
            ['hardware_category_id' => $category->id]
        );
    }

    public function parseFyReplacement(mixed $value): ?int
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits === '' ? null : (int) $digits;
    }

    public function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
