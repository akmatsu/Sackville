<?php

use App\Filament\Resources\TdxAssets\Pages\ListTdxAssets;
use App\Filament\Resources\TdxAssets\Pages\ViewTdxAsset;
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
        ]);
});

it('does not register create or edit routes for tdx assets', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-assets.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-assets.edit'))->toBeFalse();
});
