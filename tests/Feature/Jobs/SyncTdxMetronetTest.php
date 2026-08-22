<?php

use App\Enums\SyncRunStatus;
use App\Jobs\SyncTdxMetronet;
use App\Models\Department;
use App\Models\Division;
use App\Models\Fund;
use App\Models\GlCode;
use App\Models\ResponsibleDivision;
use App\Models\SyncRun;
use App\Models\TdxMetronetCircuit;
use App\Models\TdxMetronetCircuitCost;
use App\Support\FiscalYear;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\travelTo;

function fakeTdxMetronetRow(array $overrides = []): array
{
    // array_merge() renumbers integer keys (2673, 2675, ...), which are TDX
    // custom attribute IDs here, not list indexes — use `+` to preserve them.
    return $overrides + [
        'AssetID' => 6104,
        'LocationName' => 'Mat-Su Borough DSJ Building',
        'StatusName' => 'Active',
        2712 => '1 Gig',
        2676 => 'MNET3100.01',
        2675 => 3178.56,
        2713 => null,
        2673 => 'Information Technology - Enterprise -  100.115.122',
        'OwningDepartmentName' => 'Information Technology',
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
function fakeTdxMetronetReports(array $rowSets): void
{
    $sequence = Http::sequence();

    foreach ($rowSets as $rows) {
        $sequence->push(['DataRows' => $rows]);
    }

    Http::fake([
        '*/auth' => Http::response('fake-jwt-token', 200),
        '*/reports/984*' => $sequence,
    ]);
}

it('creates a Metronet circuit from a full TDX row', function () {
    fakeTdxMetronetReports([[fakeTdxMetronetRow()]]);

    (new SyncTdxMetronet)->handle();

    assertDatabaseHas(TdxMetronetCircuit::class, [
        'tdx_asset_id' => '6104',
        'status' => 'Active',
        'location_name' => 'Mat-Su Borough DSJ Building',
        'circuit_number' => 'MNET3100.01',
        'speed' => '1 Gig',
    ]);

    $circuit = TdxMetronetCircuit::where('tdx_asset_id', '6104')->firstOrFail();

    assertDatabaseHas(TdxMetronetCircuitCost::class, [
        'tdx_metronet_circuit_id' => $circuit->id,
        'fiscal_year' => FiscalYear::current(),
        'monthly_cost' => 3178.56,
        'yearly_cost' => 38142.72,
    ]);

    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-metronet',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});

it('leaves yearly_cost null when TDX gives no monthly cost', function () {
    fakeTdxMetronetReports([[fakeTdxMetronetRow([2675 => null])]]);

    (new SyncTdxMetronet)->handle();

    $circuit = TdxMetronetCircuit::where('tdx_asset_id', '6104')->firstOrFail();

    assertDatabaseHas(TdxMetronetCircuitCost::class, [
        'tdx_metronet_circuit_id' => $circuit->id,
        'fiscal_year' => FiscalYear::current(),
        'monthly_cost' => null,
        'yearly_cost' => null,
    ]);
});

it('updates the existing circuit instead of duplicating it when synced again', function () {
    fakeTdxMetronetReports([
        [fakeTdxMetronetRow()],
        [fakeTdxMetronetRow(['StatusName' => 'Inactive'])],
    ]);

    (new SyncTdxMetronet)->handle();
    (new SyncTdxMetronet)->handle();

    assertDatabaseCount(TdxMetronetCircuit::class, 1);
    assertDatabaseHas(TdxMetronetCircuit::class, [
        'tdx_asset_id' => '6104',
        'status' => 'Inactive',
    ]);
    assertDatabaseCount(TdxMetronetCircuitCost::class, 1);
});

it('creates a new cost row instead of overwriting when synced in a new fiscal year', function () {
    travelTo('2026-06-01');

    fakeTdxMetronetReports([
        [fakeTdxMetronetRow([2675 => 100.00])],
        [fakeTdxMetronetRow([2675 => 150.00])],
    ]);

    (new SyncTdxMetronet)->handle();

    travelTo('2026-08-01');

    (new SyncTdxMetronet)->handle();

    $circuit = TdxMetronetCircuit::where('tdx_asset_id', '6104')->firstOrFail();

    assertDatabaseCount(TdxMetronetCircuitCost::class, 2);
    assertDatabaseHas(TdxMetronetCircuitCost::class, [
        'tdx_metronet_circuit_id' => $circuit->id,
        'fiscal_year' => 26,
        'monthly_cost' => 100.00,
    ]);
    assertDatabaseHas(TdxMetronetCircuitCost::class, [
        'tdx_metronet_circuit_id' => $circuit->id,
        'fiscal_year' => 27,
        'monthly_cost' => 150.00,
    ]);
});

it('marks a circuit absent from the current TDX response as surplus without deleting it', function () {
    fakeTdxMetronetReports([
        [fakeTdxMetronetRow(['AssetID' => 1]), fakeTdxMetronetRow(['AssetID' => 2])],
        [fakeTdxMetronetRow(['AssetID' => 1])],
    ]);

    (new SyncTdxMetronet)->handle();
    (new SyncTdxMetronet)->handle();

    assertDatabaseCount(TdxMetronetCircuit::class, 2);
    assertDatabaseHas(TdxMetronetCircuit::class, ['tdx_asset_id' => '1', 'status' => 'Active']);
    assertDatabaseHas(TdxMetronetCircuit::class, ['tdx_asset_id' => '2', 'status' => 'Surplus']);
});

it('does not mark existing circuits as surplus when TDX returns an empty response', function () {
    fakeTdxMetronetReports([
        [fakeTdxMetronetRow(['AssetID' => 1])],
        [],
    ]);

    (new SyncTdxMetronet)->handle();
    (new SyncTdxMetronet)->handle();

    assertDatabaseHas(TdxMetronetCircuit::class, ['tdx_asset_id' => '1', 'status' => 'Active']);
});

it('resolves the responsible department/division and the GL code from the funding field', function () {
    fakeTdxMetronetReports([[fakeTdxMetronetRow([2673 => 'Community Development - Community Pools -  100.115.122'])]]);

    (new SyncTdxMetronet)->handle();

    $circuit = TdxMetronetCircuit::where('tdx_asset_id', '6104')->firstOrFail();

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
    fakeTdxMetronetReports([[fakeTdxMetronetRow([2673 => 'Not a parseable funding string'])]]);

    (new SyncTdxMetronet)->handle();

    assertDatabaseHas(TdxMetronetCircuit::class, [
        'tdx_asset_id' => '6104',
        'gl_code_id' => null,
    ]);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-metronet',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});

it('counts a row missing an AssetID as failed but still syncs the rest, marking the run partial', function () {
    fakeTdxMetronetReports([[
        fakeTdxMetronetRow(['AssetID' => 1]),
        collect(fakeTdxMetronetRow(['AssetID' => 2]))->except(['AssetID'])->all(),
    ]]);

    (new SyncTdxMetronet)->handle();

    assertDatabaseCount(TdxMetronetCircuit::class, 1);
    assertDatabaseHas(TdxMetronetCircuit::class, ['tdx_asset_id' => '1']);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-metronet',
        'status' => SyncRunStatus::Partial->value,
        'records_synced' => 1,
        'records_failed' => 1,
    ]);
});

it('records a failed sync run when TDX authentication fails', function () {
    Http::fake([
        '*/auth' => Http::response('Unauthorized', 401),
    ]);

    expect(fn () => (new SyncTdxMetronet)->handle())
        ->toThrow(RequestException::class);

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-metronet',
        'status' => SyncRunStatus::Failed->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('has a stable unique id so duplicate runs are not queued concurrently', function () {
    expect((new SyncTdxMetronet)->uniqueId())->toBe('tdx-metronet-sync');
});

it('updates a pre-created running sync run instead of creating a new one', function () {
    $syncRun = SyncRun::factory()->running()->create(['integration' => 'tdx-metronet']);

    fakeTdxMetronetReports([[fakeTdxMetronetRow(['AssetID' => 1])]]);

    (new SyncTdxMetronet($syncRun->id))->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'id' => $syncRun->id,
        'integration' => 'tdx-metronet',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 1,
        'records_failed' => 0,
    ]);
});
