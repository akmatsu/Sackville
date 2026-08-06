<?php

use App\Filament\Resources\TdxAssets\Pages\ListTdxAssets;
use App\Filament\Resources\TdxAssets\Pages\ViewTdxAsset;
use App\Models\Division;
use App\Models\GlCode;
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
    $division = Division::factory()->create();
    $glCode = GlCode::factory()->for($division)->create();

    $asset = TdxAsset::factory()->create([
        'assigned_division_id' => $division->id,
        'assigned_location_name' => 'Willow',
        'gl_code_id' => $glCode->id,
    ]);

    livewire(ListTdxAssets::class)
        ->assertTableColumnStateSet('division.name', $division->name, $asset)
        ->assertTableColumnStateSet('assigned_location_name', 'Willow', $asset)
        ->assertTableColumnStateSet('glCode.code_string', $glCode->code_string, $asset);

    livewire(ViewTdxAsset::class, ['record' => $asset->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'assigned_location_name' => 'Willow',
        ]);
});

it('does not register create or edit routes for tdx assets', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-assets.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-assets.edit'))->toBeFalse();
});
