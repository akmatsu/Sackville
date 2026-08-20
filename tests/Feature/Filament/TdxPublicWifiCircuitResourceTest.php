<?php

use App\Filament\Resources\TdxPublicWifiCircuits\Pages\ListTdxPublicWifiCircuits;
use App\Filament\Resources\TdxPublicWifiCircuits\Pages\ViewTdxPublicWifiCircuit;
use App\Models\Division;
use App\Models\GlCode;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\TdxPublicWifiCircuit;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists public wifi circuits', function () {
    $circuits = TdxPublicWifiCircuit::factory()->count(3)->create();

    livewire(ListTdxPublicWifiCircuits::class)
        ->assertCanSeeTableRecords($circuits);
});

it('views a public wifi circuit', function () {
    $circuit = TdxPublicWifiCircuit::factory()->create();

    livewire(ViewTdxPublicWifiCircuit::class, ['record' => $circuit->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'tdx_asset_id' => $circuit->tdx_asset_id,
            'location_name' => $circuit->location_name,
            'status' => $circuit->status,
            'po_number' => $circuit->po_number,
        ]);
});

it('shows the status column in the table', function () {
    $circuit = TdxPublicWifiCircuit::factory()->create(['status' => 'Surplus']);

    livewire(ListTdxPublicWifiCircuits::class)
        ->assertTableColumnStateSet('status', 'Surplus', $circuit);
});

it('shows the responsible division, location, and GL code in the table and view page', function () {
    $glCodeDivision = Division::factory()->create();
    $glCode = GlCode::factory()->for($glCodeDivision)->create();

    $responsibleDivision = ResponsibleDivision::factory()->create();
    $responsibleLocation = ResponsibleLocation::factory()->create(['responsible_division_id' => $responsibleDivision->id, 'name' => 'Willow']);

    $circuit = TdxPublicWifiCircuit::factory()->create([
        'responsible_division_id' => $responsibleDivision->id,
        'responsible_location_id' => $responsibleLocation->id,
        'gl_code_id' => $glCode->id,
    ]);

    livewire(ListTdxPublicWifiCircuits::class)
        ->assertTableColumnStateSet('responsibleDivision.name', $responsibleDivision->name, $circuit)
        ->assertTableColumnStateSet('responsibleLocation.name', 'Willow', $circuit)
        ->assertTableColumnStateSet('glCode.code_string', $glCode->code_string, $circuit);

    livewire(ViewTdxPublicWifiCircuit::class, ['record' => $circuit->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'responsibleLocation.name' => 'Willow',
        ]);
});

it('does not register create or edit routes for public wifi circuits', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-public-wifi-circuits.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-public-wifi-circuits.edit'))->toBeFalse();
});
