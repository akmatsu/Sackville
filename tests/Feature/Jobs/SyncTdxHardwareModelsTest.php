<?php

use App\Enums\SyncRunStatus;
use App\Jobs\SyncTdxHardwareModels;
use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\SyncRun;
use App\Models\TdxAsset;
use App\Models\Vendor;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

function fakeTdxWorkstationRow(array $overrides = []): array
{
    // array_merge() renumbers integer keys (2114, 2111, ...), which are TDX
    // custom attribute IDs here, not list indexes — use `+` to preserve them.
    return $overrides + [
        'AssetID' => 352,
        'Name' => 'AD-DSJ-3GQHZ44',
        'SerialNumber' => '3GQHZ44',
        'ExternalID' => 'B3GQHZ44',
        'Tag' => '3GQHZ44',
        'ManufacturerName' => 'Dell',
        'ProductModelName' => 'Precision 3460',
        'SupplierName' => 'GCSIT',
        'LocationName' => 'Mat-Su Borough DSJ Building',
        'LocationRoomName' => '',
        'StatusName' => 'Production',
        'OwningCustomerName' => 'Nikki Hyson',
        2114 => 'Nikki Hyson',
        2110 => 'Assembly - HR -  100.115.122',
        2111 => 'FY30',
        2113 => '2029-09-08T00:00:00',
        2315 => 'AD.MATSUGOV.US',
    ];
}

/**
 * Registers one report response per element of $rowSets, consumed in order
 * across successive job runs. A single Http::fake() call with a plain
 * response (rather than a sequence) always answers with the FIRST
 * registered stub for a given URL pattern — Laravel resolves fakes via
 * ->first() over every registered stub, so calling Http::fake() again
 * mid-test does not replace the earlier one.
 *
 * @param  list<list<array<int|string, mixed>>>  $rowSets
 */
function fakeTdxReports(array $rowSets): void
{
    $sequence = Http::sequence();

    foreach ($rowSets as $rows) {
        $sequence->push(['DataRows' => $rows]);
    }

    Http::fake([
        '*/auth' => Http::response('fake-jwt-token', 200),
        '*/reports/362*' => $sequence,
    ]);
}

it('creates a tdx asset with its vendor, category, and hardware model from a full TDX row', function () {
    fakeTdxReports([[fakeTdxWorkstationRow()]]);

    (new SyncTdxHardwareModels)->handle();

    assertDatabaseHas(Vendor::class, ['name' => 'Dell']);
    assertDatabaseHas(HardwareCategory::class, ['name' => 'Workstation']);

    $vendor = Vendor::where('name', 'Dell')->firstOrFail();
    assertDatabaseHas(HardwareModel::class, [
        'vendor_id' => $vendor->id,
        'name' => 'Precision 3460',
    ]);

    $model = HardwareModel::where('vendor_id', $vendor->id)->firstOrFail();
    assertDatabaseHas(TdxAsset::class, [
        'tdx_asset_id' => '352',
        'status' => 'Production',
        'description' => 'Nikki Hyson',
        'asset_tag' => '3GQHZ44',
        'serial' => '3GQHZ44',
        'hardware_model_id' => $model->id,
        'assigned_user_upn' => 'Nikki Hyson',
        'fy_replacement' => 30,
        'warranty_ends_at' => '2029-09-08 00:00:00',
    ]);

    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});

it('updates the existing tdx asset instead of duplicating it when synced again', function () {
    fakeTdxReports([
        [fakeTdxWorkstationRow()],
        [fakeTdxWorkstationRow(['StatusName' => 'In Repair'])],
    ]);

    (new SyncTdxHardwareModels)->handle();
    (new SyncTdxHardwareModels)->handle();

    assertDatabaseCount(TdxAsset::class, 1);
    assertDatabaseHas(TdxAsset::class, [
        'tdx_asset_id' => '352',
        'status' => 'In Repair',
    ]);
});

it('marks an asset absent from the current TDX response as surplus without deleting it', function () {
    fakeTdxReports([
        [fakeTdxWorkstationRow(['AssetID' => 1]), fakeTdxWorkstationRow(['AssetID' => 2])],
        [fakeTdxWorkstationRow(['AssetID' => 1])],
    ]);

    (new SyncTdxHardwareModels)->handle();
    (new SyncTdxHardwareModels)->handle();

    assertDatabaseCount(TdxAsset::class, 2);
    assertDatabaseHas(TdxAsset::class, ['tdx_asset_id' => '1', 'status' => 'Production']);
    assertDatabaseHas(TdxAsset::class, ['tdx_asset_id' => '2', 'status' => 'Surplus']);
});

it('does not mark existing assets as surplus when TDX returns an empty response', function () {
    fakeTdxReports([
        [fakeTdxWorkstationRow(['AssetID' => 1])],
        [],
    ]);

    (new SyncTdxHardwareModels)->handle();
    (new SyncTdxHardwareModels)->handle();

    assertDatabaseHas(TdxAsset::class, ['tdx_asset_id' => '1', 'status' => 'Production']);
});

it('counts a malformed row as failed but still syncs the rest, marking the run partial', function () {
    fakeTdxReports([[
        fakeTdxWorkstationRow(['AssetID' => 1]),
        collect(fakeTdxWorkstationRow(['AssetID' => 2]))->except(['AssetID'])->all(),
    ]]);

    (new SyncTdxHardwareModels)->handle();

    assertDatabaseCount(TdxAsset::class, 1);
    assertDatabaseHas(TdxAsset::class, ['tdx_asset_id' => '1']);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx',
        'status' => SyncRunStatus::Partial->value,
        'records_synced' => 1,
        'records_failed' => 1,
    ]);
});

it('leaves hardware_model_id null when TDX does not supply a manufacturer or model', function () {
    fakeTdxReports([[fakeTdxWorkstationRow(['ManufacturerName' => '', 'ProductModelName' => ''])]]);

    (new SyncTdxHardwareModels)->handle();

    assertDatabaseHas(TdxAsset::class, [
        'tdx_asset_id' => '352',
        'hardware_model_id' => null,
    ]);
});

it('resolves the responsible department/division and the GL code as two independent hierarchies', function () {
    $publicWorks = Department::factory()->create(['code' => '150', 'name' => 'Public Works']);
    $projectMgmt = Division::factory()->create([
        'department_code' => $publicWorks->code,
        'code' => '900',
        'name' => 'Project Mgmt',
    ]);

    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Public Works - Project Mgmt -  100.115.122'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();

    // The "responsible" org (who it's for) resolves to the real Public
    // Works / Project Mgmt division...
    expect($asset->assigned_department_code)->toBe('150');
    expect($asset->assigned_division_id)->toBe($projectMgmt->id);
    expect($asset->assigned_location_name)->toBeNull();

    // ...while the GL code (what it's coded to) is IT's own 100.115.122,
    // an entirely different division than "Project Mgmt".
    $glCode = GlCode::findOrFail($asset->gl_code_id);
    expect($glCode->code_string)->toBe('100.115.122.434.000');
    expect($glCode->division_id)->not->toBe($projectMgmt->id);

    $fund = Fund::findOrFail('100');
    expect($fund->name)->toBe('General Fund');
    expect($fund->active)->toBeTrue();

    $glDepartment = Department::findOrFail('115');
    expect($glDepartment->name)->toBe('Information Technology');
    expect($glDepartment->active)->toBeTrue();

    // The GL division (122) has no name TDX can give us, so it's an
    // inactive placeholder pending review in the admin screens.
    $glDivision = Division::where('code', '122')->firstOrFail();
    expect($glDivision->active)->toBeFalse();
});

it('captures the optional third level as a location name', function () {
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Community Development - Library - Willow -  200.170.507'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();

    expect($asset->assigned_location_name)->toBe('Willow');

    $glCode = GlCode::findOrFail($asset->gl_code_id);
    expect($glCode->code_string)->toBe('200.170.507.434.000');
    expect(Fund::findOrFail('200')->fund_type->value)->toBe('non_areawide');
});

it('falls back to the raw department name when it does not match a known department', function () {
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Some Unknown Department - Some Division -  100.115.122'])]]);

    (new SyncTdxHardwareModels)->handle();

    assertDatabaseHas(TdxAsset::class, [
        'tdx_asset_id' => '352',
        'assigned_department_code' => 'Some Unknown Department',
        'assigned_division_id' => null,
    ]);
});

it('leaves gl_code_id null without failing the row when the funding field is malformed', function () {
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Not a parseable funding string'])]]);

    (new SyncTdxHardwareModels)->handle();

    assertDatabaseHas(TdxAsset::class, [
        'tdx_asset_id' => '352',
        'gl_code_id' => null,
    ]);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});

it('records a failed sync run when TDX authentication fails', function () {
    Http::fake([
        '*/auth' => Http::response('Unauthorized', 401),
    ]);

    expect(fn () => (new SyncTdxHardwareModels)->handle())
        ->toThrow(RequestException::class);

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx',
        'status' => SyncRunStatus::Failed->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('has a stable unique id so duplicate runs are not queued concurrently', function () {
    expect((new SyncTdxHardwareModels)->uniqueId())->toBe('tdx-hardware-models-sync');
});
