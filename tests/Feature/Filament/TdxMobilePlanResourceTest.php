<?php

use App\Filament\Resources\TdxMobilePlans\Pages\ListTdxMobilePlans;
use App\Filament\Resources\TdxMobilePlans\Pages\ViewTdxMobilePlan;
use App\Filament\Resources\TdxMobilePlans\RelationManagers\DevicesRelationManager;
use App\Models\Division;
use App\Models\GlCode;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\TdxAsset;
use App\Models\TdxMobilePlan;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists mobile plans', function () {
    $plans = TdxMobilePlan::factory()->count(3)->create();

    livewire(ListTdxMobilePlans::class)
        ->assertCanSeeTableRecords($plans);
});

it('views a mobile plan', function () {
    $plan = TdxMobilePlan::factory()->create();

    livewire(ViewTdxMobilePlan::class, ['record' => $plan->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'tdx_asset_id' => $plan->tdx_asset_id,
            'asset_tag' => $plan->asset_tag,
            'carrier' => $plan->carrier,
            'status' => $plan->status,
            'plan_status' => $plan->plan_status,
            'po_number' => $plan->po_number,
        ]);
});

it('shows status and plan status columns in the table', function () {
    $plan = TdxMobilePlan::factory()->create([
        'status' => 'Surplus',
        'plan_status' => 'Suspended',
    ]);

    livewire(ListTdxMobilePlans::class)
        ->assertTableColumnStateSet('status', 'Surplus', $plan)
        ->assertTableColumnStateSet('plan_status', 'Suspended', $plan);
});

it('shows the responsible division, location, and GL code in the table and view page', function () {
    $glCodeDivision = Division::factory()->create();
    $glCode = GlCode::factory()->for($glCodeDivision)->create();

    $responsibleDivision = ResponsibleDivision::factory()->create();
    $responsibleLocation = ResponsibleLocation::factory()->create(['responsible_division_id' => $responsibleDivision->id, 'name' => 'Willow']);

    $plan = TdxMobilePlan::factory()->create([
        'responsible_division_id' => $responsibleDivision->id,
        'responsible_location_id' => $responsibleLocation->id,
        'gl_code_id' => $glCode->id,
    ]);

    livewire(ListTdxMobilePlans::class)
        ->assertTableColumnStateSet('responsibleDivision.name', $responsibleDivision->name, $plan)
        ->assertTableColumnStateSet('responsibleLocation.name', 'Willow', $plan)
        ->assertTableColumnStateSet('glCode.code_string', $glCode->code_string, $plan);

    livewire(ViewTdxMobilePlan::class, ['record' => $plan->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'responsibleLocation.name' => 'Willow',
        ]);
});

it('does not register create or edit routes for mobile plans', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-mobile-plans.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.tdx-mobile-plans.edit'))->toBeFalse();
});

it('lists devices linked to the plan by matching serial numbers', function () {
    $plan = TdxMobilePlan::factory()->create(['serial' => '9073550563']);
    $device = TdxAsset::factory()->create(['plan_serial' => '9073550563']);
    TdxAsset::factory()->create(['plan_serial' => 'unrelated-serial']);

    livewire(DevicesRelationManager::class, [
        'ownerRecord' => $plan,
        'pageClass' => ViewTdxMobilePlan::class,
    ])->assertCanSeeTableRecords([$device]);
});
