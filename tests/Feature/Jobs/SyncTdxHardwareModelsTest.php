<?php

use App\Enums\SyncRunStatus;
use App\Enums\TdxAssetSource;
use App\Jobs\SyncTdxHardwareModels;
use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
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
        'source' => TdxAssetSource::Workstation->value,
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

it('does not mark a mobile-sourced tdx asset as surplus during a workstation sync', function () {
    $mobileAsset = TdxAsset::factory()->create(['source' => TdxAssetSource::Mobile, 'status' => 'Production']);

    fakeTdxReports([[fakeTdxWorkstationRow(['AssetID' => 1])]]);

    (new SyncTdxHardwareModels)->handle();

    expect($mobileAsset->refresh()->status)->toBe('Production');
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
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Public Works - Project Mgmt -  100.115.122'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();

    // The "responsible" org (who it's for) is stored as plain text and
    // resolved into its own isolated table...
    expect($asset->assigned_department_code)->toBe('Public Works');

    $division = ResponsibleDivision::findOrFail($asset->responsible_division_id);
    expect($division->department_name)->toBe('Public Works');
    expect($division->name)->toBe('Project Mgmt');
    expect($division->active)->toBeTrue();
    expect($asset->responsible_location_id)->toBeNull();

    // ...while the GL code (what it's coded to) is IT's own 100.115.122,
    // resolved entirely independently via the real chart-of-accounts tables.
    $glCode = GlCode::findOrFail($asset->gl_code_id);
    expect($glCode->code_string)->toBe('100.115.122.434.000');

    $fund = Fund::findOrFail('100');
    expect($fund->name)->toBe('General Fund');
    expect($fund->active)->toBeTrue();

    $glDepartment = Department::findOrFail('115');
    expect($glDepartment->name)->toBe('Information Technology');
    expect($glDepartment->active)->toBeTrue();

    // The GL division (122) has no name TDX can give us, so it's an
    // inactive placeholder pending review in the admin screens — a
    // completely separate row from the responsible ResponsibleDivision above.
    $glDivision = Division::where('code', '122')->firstOrFail();
    expect($glDivision->active)->toBeFalse();
});

it('leaves the responsible location null when the funding field has no third segment', function () {
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Public Works - Project Mgmt -  100.115.122'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();

    expect($asset->responsible_division_id)->not->toBeNull();
    expect($asset->responsible_location_id)->toBeNull();
    expect(ResponsibleLocation::count())->toBe(0);
});

it('captures the optional third level as a responsible location nested under the resolved division', function () {
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Community Development - Library - Willow -  200.170.507'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();

    $division = ResponsibleDivision::findOrFail($asset->responsible_division_id);
    expect($division->department_name)->toBe('Community Development');
    expect($division->name)->toBe('Library');

    $location = ResponsibleLocation::findOrFail($asset->responsible_location_id);
    expect($location->name)->toBe('Willow');
    expect($location->responsible_division_id)->toBe($division->id);

    $glCode = GlCode::findOrFail($asset->gl_code_id);
    expect($glCode->code_string)->toBe('200.170.507.434.000');
    expect(Fund::findOrFail('200')->fund_type->value)->toBe('non_areawide');
});

it('always stores the raw responsible department name, with no lookup against the real departments table', function () {
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Some Unknown Department - Some Division -  100.115.122'])]]);

    (new SyncTdxHardwareModels)->handle();

    assertDatabaseHas(TdxAsset::class, [
        'tdx_asset_id' => '352',
        'assigned_department_code' => 'Some Unknown Department',
    ]);

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();
    expect(ResponsibleDivision::findOrFail($asset->responsible_division_id)->department_name)->toBe('Some Unknown Department');
});

it('matches an existing responsible division case- and whitespace-insensitively', function () {
    $gis = ResponsibleDivision::factory()->create(['department_name' => 'Information Technology', 'name' => 'GIS']);

    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Information Technology -  gis  -  100.115.900'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();
    expect($asset->responsible_division_id)->toBe($gis->id);
    expect(ResponsibleDivision::count())->toBe(1);
});

it('matches an existing responsible location case- and whitespace-insensitively', function () {
    $division = ResponsibleDivision::factory()->create(['department_name' => 'Community Development', 'name' => 'Library']);
    $willow = ResponsibleLocation::factory()->create(['responsible_division_id' => $division->id, 'name' => 'Willow']);

    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Community Development - Library -  willow  -  200.170.507'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();
    expect($asset->responsible_location_id)->toBe($willow->id);
    expect(ResponsibleLocation::count())->toBe(1);
});

it('auto-creates an unmatched responsible division, active and independent of any GL code', function () {
    fakeTdxReports([[fakeTdxWorkstationRow([2110 => 'Information Technology - GIS -  100.115.900'])]]);

    (new SyncTdxHardwareModels)->handle();

    $asset = TdxAsset::where('tdx_asset_id', '352')->firstOrFail();
    $division = ResponsibleDivision::findOrFail($asset->responsible_division_id);

    expect($division->department_name)->toBe('Information Technology');
    expect($division->name)->toBe('GIS');
    expect($division->active)->toBeTrue();
});

it('does not duplicate auto-created responsible division or location rows across repeated syncs', function () {
    fakeTdxReports([
        [fakeTdxWorkstationRow([2110 => 'Community Development - Library - Willow -  200.170.507'])],
        [fakeTdxWorkstationRow([2110 => 'Community Development - Library - Willow -  200.170.507'])],
    ]);

    (new SyncTdxHardwareModels)->handle();
    (new SyncTdxHardwareModels)->handle();

    assertDatabaseCount(ResponsibleDivision::class, 1);
    assertDatabaseCount(ResponsibleLocation::class, 1);
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

it('updates a pre-created running sync run instead of creating a new one', function () {
    $syncRun = SyncRun::factory()->running()->create(['integration' => 'tdx']);

    fakeTdxReports([[fakeTdxWorkstationRow(['AssetID' => 1])]]);

    (new SyncTdxHardwareModels($syncRun->id))->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'id' => $syncRun->id,
        'integration' => 'tdx',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});
