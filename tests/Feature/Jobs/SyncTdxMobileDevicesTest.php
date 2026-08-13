<?php

use App\Enums\SyncRunStatus;
use App\Enums\TdxAssetSource;
use App\Jobs\SyncTdxMobileDevices;
use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\SyncRun;
use App\Models\TdxAsset;
use App\Models\TdxMobilePlan;
use App\Models\Vendor;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

/**
 * Real sample row for a mobile plan (a cellular line/billing account), as
 * returned by TDX report 363.
 */
function fakeTdxMobilePlanRow(array $overrides = []): array
{
    return $overrides + [
        'AssetID' => 3209,
        'Name' => '9073550563',
        'SerialNumber' => '9073550563',
        'ExternalID' => '9073550563',
        'Tag' => '9073550563',
        'ManufacturerName' => 'AT&T',
        'ProductModelName' => 'BAN Primary: 287283229772',
        'SupplierName' => 'None',
        'LocationName' => '',
        'LocationRoomName' => '',
        'StatusName' => 'Production',
        'OwningCustomerName' => 'George Hays',
        2111 => null,
        2114 => 'George Hays',
        'ParentSerial' => null,
        2110 => 'Assembly - Administration -  100.115.122',
        2389 => 'Active',
        'ProductTypeName' => 'Plan',
        2112 => '27-0012 LINE A AW Phone/iPad',
        2390 => 'FirstNet Mobile Unlimited Enhanced for iPhone w/VVM, Tethering and Mobile Hotspot',
    ];
}

/**
 * Real sample row for a mobile device (a physical phone), as returned by
 * TDX report 363. Its ParentSerial matches fakeTdxMobilePlanRow()'s serial.
 */
function fakeTdxMobileDeviceRow(array $overrides = []): array
{
    return $overrides + [
        'AssetID' => 3844,
        'Name' => 'iPhone',
        'SerialNumber' => 'DX3CJNZ6KXKN',
        'ExternalID' => 'BDX3CJNZ6KXKN',
        'Tag' => 'DX3CJNZ6KXKN',
        'ManufacturerName' => 'Apple',
        'ProductModelName' => 'iPhone XR',
        'SupplierName' => 'None',
        'LocationName' => '',
        'LocationRoomName' => '',
        'StatusName' => 'Production',
        'OwningCustomerName' => 'George Hays',
        2111 => 'FY25',
        2114 => 'George Hays',
        'ParentSerial' => '9073550563',
        2110 => 'Assembly - Administration -  100.115.122',
        2389 => 'Active',
        'ProductTypeName' => 'Phone',
        2112 => null,
        2390 => null,
    ];
}

/**
 * Same pattern as SyncTdxHardwareModelsTest's fakeTdxReports(), targeting
 * report 363 instead of 362.
 *
 * @param  list<list<array<int|string, mixed>>>  $rowSets
 */
function fakeTdxMobileReports(array $rowSets): void
{
    $sequence = Http::sequence();

    foreach ($rowSets as $rows) {
        $sequence->push(['DataRows' => $rows]);
    }

    Http::fake([
        '*/auth' => Http::response('fake-jwt-token', 200),
        '*/reports/363*' => $sequence,
    ]);
}

it('records a successful sync run with the count of rows TDX returned', function () {
    fakeTdxMobileReports([[
        fakeTdxMobileDeviceRow(['AssetID' => 1]),
        fakeTdxMobileDeviceRow(['AssetID' => 2]),
    ]]);

    (new SyncTdxMobileDevices)->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-mobile',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 2,
        'records_failed' => 0,
    ]);
});

it('records a successful, zero-count sync run when TDX returns no mobile rows', function () {
    fakeTdxMobileReports([[]]);

    (new SyncTdxMobileDevices)->handle();

    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-mobile',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('records a failed sync run when TDX authentication fails', function () {
    Http::fake([
        '*/auth' => Http::response('Unauthorized', 401),
    ]);

    expect(fn () => (new SyncTdxMobileDevices)->handle())
        ->toThrow(RequestException::class);

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-mobile',
        'status' => SyncRunStatus::Failed->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('has a stable unique id so duplicate runs are not queued concurrently', function () {
    expect((new SyncTdxMobileDevices)->uniqueId())->toBe('tdx-mobile-devices-sync');
});

it('updates a pre-created running sync run instead of creating a new one', function () {
    $syncRun = SyncRun::factory()->running()->create(['integration' => 'tdx-mobile']);

    fakeTdxMobileReports([[fakeTdxMobileDeviceRow(['AssetID' => 1])]]);

    (new SyncTdxMobileDevices($syncRun->id))->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'id' => $syncRun->id,
        'integration' => 'tdx-mobile',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});

it('creates a tdx_mobile_plan from a plan row, coded to 421.100, without touching vendors or hardware_models', function () {
    fakeTdxMobileReports([[fakeTdxMobilePlanRow()]]);

    (new SyncTdxMobileDevices)->handle();

    assertDatabaseCount(TdxMobilePlan::class, 1);
    assertDatabaseHas(TdxMobilePlan::class, [
        'tdx_asset_id' => '3209',
        'status' => 'Production',
        'carrier' => 'AT&T',
        'po_number' => '27-0012 LINE A AW Phone/iPad',
        'plan_status' => 'Active',
        'plan_description' => 'FirstNet Mobile Unlimited Enhanced for iPhone w/VVM, Tethering and Mobile Hotspot',
        'description' => 'George Hays',
        'asset_tag' => '9073550563',
        'serial' => '9073550563',
        'assigned_user_upn' => 'George Hays',
    ]);

    $plan = TdxMobilePlan::where('tdx_asset_id', '3209')->firstOrFail();
    $glCode = GlCode::findOrFail($plan->gl_code_id);
    expect($glCode->code_string)->toBe('100.115.122.421.100');

    expect(Vendor::where('name', 'AT&T')->exists())->toBeFalse();
    expect(HardwareModel::count())->toBe(0);
    expect(TdxAsset::count())->toBe(0);
});

it('creates a tdx_asset device row resolved under the Mobile hardware category, coded to 434.000', function () {
    fakeTdxMobileReports([[fakeTdxMobileDeviceRow()]]);

    (new SyncTdxMobileDevices)->handle();

    assertDatabaseCount(TdxAsset::class, 1);
    assertDatabaseHas(TdxAsset::class, [
        'tdx_asset_id' => '3844',
        'source' => TdxAssetSource::Mobile->value,
        'status' => 'Production',
        'product_type' => 'Phone',
        'asset_tag' => 'DX3CJNZ6KXKN',
        'serial' => 'DX3CJNZ6KXKN',
        'plan_serial' => '9073550563',
        'fy_replacement' => 25,
    ]);

    assertDatabaseHas(Vendor::class, ['name' => 'Apple']);
    assertDatabaseHas(HardwareCategory::class, ['name' => 'Mobile']);

    $asset = TdxAsset::where('tdx_asset_id', '3844')->firstOrFail();
    $model = HardwareModel::findOrFail($asset->hardware_model_id);
    expect($model->name)->toBe('iPhone XR');
    expect($model->category->name)->toBe('Mobile');

    $glCode = GlCode::findOrFail($asset->gl_code_id);
    expect($glCode->code_string)->toBe('100.115.122.434.000');

    expect(TdxMobilePlan::count())->toBe(0);
});

it('resolves a device to its plan (and the plan to its devices) by matching serial numbers', function () {
    fakeTdxMobileReports([[fakeTdxMobilePlanRow(), fakeTdxMobileDeviceRow()]]);

    (new SyncTdxMobileDevices)->handle();

    $plan = TdxMobilePlan::where('tdx_asset_id', '3209')->firstOrFail();
    $device = TdxAsset::where('tdx_asset_id', '3844')->firstOrFail();

    expect($device->plan)->not->toBeNull();
    expect($device->plan->id)->toBe($plan->id);
    expect($plan->devices)->toHaveCount(1);
    expect($plan->devices->first()->id)->toBe($device->id);
});

it('only marks source=mobile tdx_assets as surplus, leaving a workstation-sourced row untouched', function () {
    $workstation = TdxAsset::factory()->create(['source' => TdxAssetSource::Workstation, 'status' => 'Production']);
    $staleMobileDevice = TdxAsset::factory()->create(['source' => TdxAssetSource::Mobile, 'status' => 'Production']);
    $stalePlan = TdxMobilePlan::factory()->create(['status' => 'Production']);

    fakeTdxMobileReports([[fakeTdxMobileDeviceRow()]]);

    (new SyncTdxMobileDevices)->handle();

    expect($workstation->refresh()->status)->toBe('Production');
    expect($staleMobileDevice->refresh()->status)->toBe('Surplus');
    expect($stalePlan->refresh()->status)->toBe('Surplus');
});

it('counts a malformed row as failed but still syncs the rest, marking the run partial', function () {
    fakeTdxMobileReports([[
        fakeTdxMobileDeviceRow(),
        collect(fakeTdxMobilePlanRow())->except(['AssetID'])->all(),
    ]]);

    (new SyncTdxMobileDevices)->handle();

    assertDatabaseCount(TdxAsset::class, 1);
    assertDatabaseCount(TdxMobilePlan::class, 0);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-mobile',
        'status' => SyncRunStatus::Partial->value,
        'records_synced' => 1,
        'records_failed' => 1,
    ]);
});
