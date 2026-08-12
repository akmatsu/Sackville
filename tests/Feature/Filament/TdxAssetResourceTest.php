<?php

use App\Filament\Resources\TdxAssets\Pages\ListTdxAssets;
use App\Filament\Resources\TdxAssets\Pages\ViewTdxAsset;
use App\Models\Division;
use App\Models\GlCode;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\TdxAsset;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists tdx assets', function () {
    $assets = TdxAsset::factory()->count(3)->create();

    livewire(ListTdxAssets::class)
        ->assertCanSeeTableRecords($assets);
});

it('views a tdx asset', function () {
    $asset = TdxAsset::factory()->create();

    livewire(ViewTdxAsset::class, ['record' => $asset->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'tdx_asset_id' => $asset->tdx_asset_id,
            'asset_tag' => $asset->asset_tag,
            'status' => $asset->status,
            'description' => $asset->description,
            'warranty_ends_at' => $asset->warranty_ends_at,
        ]);
});

it('shows status and warranty columns in the table', function () {
    $asset = TdxAsset::factory()->create([
        'status' => 'Surplus',
        'warranty_ends_at' => '2029-09-08',
    ]);

    livewire(ListTdxAssets::class)
        ->assertTableColumnStateSet('status', 'Surplus', $asset)
        ->assertTableColumnStateSet('warranty_ends_at', $asset->warranty_ends_at, $asset);
});

it('shows the responsible division, location, and GL code in the table and view page', function () {
    $glCodeDivision = Division::factory()->create();
    $glCode = GlCode::factory()->for($glCodeDivision)->create();

    $responsibleDivision = ResponsibleDivision::factory()->create();
    $responsibleLocation = ResponsibleLocation::factory()->create(['responsible_division_id' => $responsibleDivision->id, 'name' => 'Willow']);

    $asset = TdxAsset::factory()->create([
        'responsible_division_id' => $responsibleDivision->id,
        'responsible_location_id' => $responsibleLocation->id,
        'gl_code_id' => $glCode->id,
    ]);

    livewire(ListTdxAssets::class)
        ->assertTableColumnStateSet('responsibleDivision.name', $responsibleDivision->name, $asset)
        ->assertTableColumnStateSet('responsibleLocation.name', 'Willow', $asset)
        ->assertTableColumnStateSet('glCode.code_string', $glCode->code_string, $asset);

    livewire(ViewTdxAsset::class, ['record' => $asset->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'responsibleLocation.name' => 'Willow',
        ]);
});

it('does not register create or edit routes for tdx assets', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-assets.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-assets.edit'))->toBeFalse();
});
