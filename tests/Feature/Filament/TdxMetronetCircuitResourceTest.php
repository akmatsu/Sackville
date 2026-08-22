<?php

use App\Filament\Resources\TdxMetronetCircuits\Pages\ListTdxMetronetCircuits;
use App\Filament\Resources\TdxMetronetCircuits\Pages\ViewTdxMetronetCircuit;
use App\Models\Division;
use App\Models\GlCode;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\TdxMetronetCircuit;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists Metronet circuits', function () {
    $circuits = TdxMetronetCircuit::factory()->count(3)->create();

    livewire(ListTdxMetronetCircuits::class)
        ->assertCanSeeTableRecords($circuits);
});

it('views a Metronet circuit', function () {
    $circuit = TdxMetronetCircuit::factory()->create();

    livewire(ViewTdxMetronetCircuit::class, ['record' => $circuit->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'tdx_asset_id' => $circuit->tdx_asset_id,
            'location_name' => $circuit->location_name,
            'status' => $circuit->status,
            'circuit_number' => $circuit->circuit_number,
        ]);
});

it('shows the status column in the table', function () {
    $circuit = TdxMetronetCircuit::factory()->create(['status' => 'Surplus']);

    livewire(ListTdxMetronetCircuits::class)
        ->assertTableColumnStateSet('status', 'Surplus', $circuit);
});

it('shows the responsible division, location, and GL code in the table and view page', function () {
    $glCodeDivision = Division::factory()->create();
    $glCode = GlCode::factory()->for($glCodeDivision)->create();

    $responsibleDivision = ResponsibleDivision::factory()->create();
    $responsibleLocation = ResponsibleLocation::factory()->create(['responsible_division_id' => $responsibleDivision->id, 'name' => 'Willow']);

    $circuit = TdxMetronetCircuit::factory()->create([
        'responsible_division_id' => $responsibleDivision->id,
        'responsible_location_id' => $responsibleLocation->id,
        'gl_code_id' => $glCode->id,
    ]);

    livewire(ListTdxMetronetCircuits::class)
        ->assertTableColumnStateSet('responsibleDivision.name', $responsibleDivision->name, $circuit)
        ->assertTableColumnStateSet('responsibleLocation.name', 'Willow', $circuit)
        ->assertTableColumnStateSet('glCode.code_string', $glCode->code_string, $circuit);

    livewire(ViewTdxMetronetCircuit::class, ['record' => $circuit->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'responsibleLocation.name' => 'Willow',
        ]);
});

it('does not register create or edit routes for Metronet circuits', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-metronet-circuits.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-metronet-circuits.edit'))->toBeFalse();
});
