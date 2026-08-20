<?php

use App\Enums\SyncRunStatus;
use App\Jobs\SyncTdxPublicWifi;
use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\ResponsibleDivision;
use App\Models\SyncRun;
use App\Models\TdxPublicWifiCircuit;
use App\Models\TdxPublicWifiCircuitCost;
use App\Support\FiscalYear;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\travelTo;

function fakeTdxPublicWifiRow(array $overrides = []): array
{
    // array_merge() renumbers integer keys (2673, 2674, ...), which are TDX
    // custom attribute IDs here, not list indexes — use `+` to preserve them.
    return $overrides + [
        'AssetID' => 5722,
        'LocationName' => 'Wasilla Pool',
        'ITAMAddress' => null,
        'StatusName' => 'Active',
        2712 => '100 Mbps',
        2674 => '27-0252 LINE ZF',
        2675 => 134.98,
        'PurchaseCost' => 0.0,
        2713 => null,
        2673 => 'Community Development - Community Pools -  100.115.122',
    ];
}

/**
 * Registers one report response per element of $rowSets, consumed in order
 * across successive job runs — see fakeTdxReports() in
 * SyncTdxHardwareModelsTest.php for why a sequence is needed instead of a
 * single Http::fake() call.
 *
 * @param  list<list<array<int|string, mixed>>>  $rowSets
 */
function fakeTdxPublicWifiReports(array $rowSets): void
{
    $sequence = Http::sequence();

    foreach ($rowSets as $rows) {
        $sequence->push(['DataRows' => $rows]);
    }

    Http::fake([
        '*/auth' => Http::response('fake-jwt-token', 200),
        '*/reports/985*' => $sequence,
    ]);
}

it('creates a public wifi circuit from a full TDX row', function () {
    fakeTdxPublicWifiReports([[fakeTdxPublicWifiRow()]]);

    (new SyncTdxPublicWifi)->handle();

    assertDatabaseHas(TdxPublicWifiCircuit::class, [
        'tdx_asset_id' => '5722',
        'status' => 'Active',
        'location_name' => 'Wasilla Pool',
        'address' => null,
        'speed' => '100 Mbps',
        'po_number' => '27-0252 LINE ZF',
    ]);

    $circuit = TdxPublicWifiCircuit::where('tdx_asset_id', '5722')->firstOrFail();

    assertDatabaseHas(TdxPublicWifiCircuitCost::class, [
        'tdx_public_wifi_circuit_id' => $circuit->id,
        'fiscal_year' => FiscalYear::current(),
        'monthly_cost' => 134.98,
        'yearly_cost' => 1619.76,
        'purchase_cost' => 0,
    ]);

    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-public-wifi',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});

it('leaves yearly_cost null when TDX gives no monthly cost', function () {
    fakeTdxPublicWifiReports([[fakeTdxPublicWifiRow([2675 => null])]]);

    (new SyncTdxPublicWifi)->handle();

    $circuit = TdxPublicWifiCircuit::where('tdx_asset_id', '5722')->firstOrFail();

    assertDatabaseHas(TdxPublicWifiCircuitCost::class, [
        'tdx_public_wifi_circuit_id' => $circuit->id,
        'fiscal_year' => FiscalYear::current(),
        'monthly_cost' => null,
        'yearly_cost' => null,
    ]);
});

it('updates the existing circuit instead of duplicating it when synced again', function () {
    fakeTdxPublicWifiReports([
        [fakeTdxPublicWifiRow()],
        [fakeTdxPublicWifiRow(['StatusName' => 'Inactive'])],
    ]);

    (new SyncTdxPublicWifi)->handle();
    (new SyncTdxPublicWifi)->handle();

    assertDatabaseCount(TdxPublicWifiCircuit::class, 1);
    assertDatabaseHas(TdxPublicWifiCircuit::class, [
        'tdx_asset_id' => '5722',
        'status' => 'Inactive',
    ]);
    assertDatabaseCount(TdxPublicWifiCircuitCost::class, 1);
});

it('creates a new cost row instead of overwriting when synced in a new fiscal year', function () {
    travelTo('2026-06-01');

    fakeTdxPublicWifiReports([
        [fakeTdxPublicWifiRow([2675 => 100.00])],
        [fakeTdxPublicWifiRow([2675 => 150.00])],
    ]);

    (new SyncTdxPublicWifi)->handle();

    travelTo('2026-08-01');

    (new SyncTdxPublicWifi)->handle();

    $circuit = TdxPublicWifiCircuit::where('tdx_asset_id', '5722')->firstOrFail();

    assertDatabaseCount(TdxPublicWifiCircuitCost::class, 2);
    assertDatabaseHas(TdxPublicWifiCircuitCost::class, [
        'tdx_public_wifi_circuit_id' => $circuit->id,
        'fiscal_year' => 26,
        'monthly_cost' => 100.00,
    ]);
    assertDatabaseHas(TdxPublicWifiCircuitCost::class, [
        'tdx_public_wifi_circuit_id' => $circuit->id,
        'fiscal_year' => 27,
        'monthly_cost' => 150.00,
    ]);
});

it('marks a circuit absent from the current TDX response as surplus without deleting it', function () {
    fakeTdxPublicWifiReports([
        [fakeTdxPublicWifiRow(['AssetID' => 1]), fakeTdxPublicWifiRow(['AssetID' => 2])],
        [fakeTdxPublicWifiRow(['AssetID' => 1])],
    ]);

    (new SyncTdxPublicWifi)->handle();
    (new SyncTdxPublicWifi)->handle();

    assertDatabaseCount(TdxPublicWifiCircuit::class, 2);
    assertDatabaseHas(TdxPublicWifiCircuit::class, ['tdx_asset_id' => '1', 'status' => 'Active']);
    assertDatabaseHas(TdxPublicWifiCircuit::class, ['tdx_asset_id' => '2', 'status' => 'Surplus']);
});

it('does not mark existing circuits as surplus when TDX returns an empty response', function () {
    fakeTdxPublicWifiReports([
        [fakeTdxPublicWifiRow(['AssetID' => 1])],
        [],
    ]);

    (new SyncTdxPublicWifi)->handle();
    (new SyncTdxPublicWifi)->handle();

    assertDatabaseHas(TdxPublicWifiCircuit::class, ['tdx_asset_id' => '1', 'status' => 'Active']);
});

it('resolves the responsible department/division and the GL code from the funding field', function () {
    fakeTdxPublicWifiReports([[fakeTdxPublicWifiRow([2673 => 'Community Development - Community Pools -  100.115.122'])]]);

    (new SyncTdxPublicWifi)->handle();

    $circuit = TdxPublicWifiCircuit::where('tdx_asset_id', '5722')->firstOrFail();

    expect($circuit->assigned_department_code)->toBe('Community Development');

    $division = ResponsibleDivision::findOrFail($circuit->responsible_division_id);
    expect($division->department_name)->toBe('Community Development');
    expect($division->name)->toBe('Community Pools');

    $glCode = GlCode::findOrFail($circuit->gl_code_id);
    expect($glCode->code_string)->toBe('100.115.122.421.100');

    $fund = Fund::findOrFail('100');
    expect($fund->name)->toBe('General Fund');

    $glDepartment = Department::findOrFail('115');
    expect($glDepartment->name)->toBe('Information Technology');

    $glDivision = Division::where('code', '122')->firstOrFail();
    expect($glDivision->active)->toBeFalse();
});

it('leaves gl_code_id null without failing the row when the funding field is malformed', function () {
    fakeTdxPublicWifiReports([[fakeTdxPublicWifiRow([2673 => 'Not a parseable funding string'])]]);

    (new SyncTdxPublicWifi)->handle();

    assertDatabaseHas(TdxPublicWifiCircuit::class, [
        'tdx_asset_id' => '5722',
        'gl_code_id' => null,
    ]);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-public-wifi',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});

it('counts a row missing an AssetID as failed but still syncs the rest, marking the run partial', function () {
    fakeTdxPublicWifiReports([[
        fakeTdxPublicWifiRow(['AssetID' => 1]),
        collect(fakeTdxPublicWifiRow(['AssetID' => 2]))->except(['AssetID'])->all(),
    ]]);

    (new SyncTdxPublicWifi)->handle();

    assertDatabaseCount(TdxPublicWifiCircuit::class, 1);
    assertDatabaseHas(TdxPublicWifiCircuit::class, ['tdx_asset_id' => '1']);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-public-wifi',
        'status' => SyncRunStatus::Partial->value,
        'records_synced' => 1,
        'records_failed' => 1,
    ]);
});

it('records a failed sync run when TDX authentication fails', function () {
    Http::fake([
        '*/auth' => Http::response('Unauthorized', 401),
    ]);

    expect(fn () => (new SyncTdxPublicWifi)->handle())
        ->toThrow(RequestException::class);

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-public-wifi',
        'status' => SyncRunStatus::Failed->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('has a stable unique id so duplicate runs are not queued concurrently', function () {
    expect((new SyncTdxPublicWifi)->uniqueId())->toBe('tdx-public-wifi-sync');
});

it('updates a pre-created running sync run instead of creating a new one', function () {
    $syncRun = SyncRun::factory()->running()->create(['integration' => 'tdx-public-wifi']);

    fakeTdxPublicWifiReports([[fakeTdxPublicWifiRow(['AssetID' => 1])]]);

    (new SyncTdxPublicWifi($syncRun->id))->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'id' => $syncRun->id,
        'integration' => 'tdx-public-wifi',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});
