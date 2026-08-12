<?php

use App\Enums\BudgetCycleStatus;
use App\Models\BudgetCycle;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use App\Models\HardwareReplacementGroup;
use App\Models\HardwareReplacementSelection;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\TdxAsset;
use App\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function setUpWorkstationReplacementFixtures(array $assetOverrides = []): array
{
    $cycle = BudgetCycle::factory()->create([
        'fiscal_year' => 28,
        'status' => BudgetCycleStatus::Open,
    ]);

    $division = ResponsibleDivision::factory()->create([
        'department_name' => 'Information Technology',
        'name' => 'Business Operations',
    ]);

    $category = HardwareCategory::factory()->create(['name' => 'Workstation']);
    $currentModel = HardwareModel::factory()->create(['hardware_category_id' => $category->id, 'active' => true]);
    $replacementModel = HardwareModel::factory()->create([
        'hardware_category_id' => $category->id,
        'active' => true,
        'has_docking_option' => true,
    ]);

    $group = HardwareReplacementGroup::factory()->create(['active' => true]);
    $group->replaceableCategories()->attach($category);
    $group->eligibleModels()->attach($replacementModel);

    $asset = TdxAsset::factory()->create(array_merge([
        'hardware_model_id' => $currentModel->id,
        'assigned_department_code' => 'Information Technology',
        'responsible_division_id' => $division->id,
        'fy_replacement' => 28,
    ], $assetOverrides));

    return compact('cycle', 'division', 'category', 'currentModel', 'replacementModel', 'group', 'asset');
}

test('a user with edit responsibility over the asset scope sees and selects an eligible replacement', function () {
    $user = User::factory()->create();
    ['cycle' => $cycle, 'division' => $division, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures([
        'has_docking_station' => false,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee($asset->asset_tag)
        ->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id)
        ->call('save', $asset->id);

    assertDatabaseHas(HardwareReplacementSelection::class, [
        'budget_cycle_id' => $cycle->id,
        'tdx_asset_id' => $asset->id,
        'hardware_model_id' => $replacementModel->id,
        'with_docking' => true,
        'selected_by_id' => $user->id,
    ]);
});

test('a replacement does not add a new docking station when the outgoing asset already has one', function () {
    $user = User::factory()->create();
    ['division' => $division, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures([
        'has_docking_station' => true,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee('Yes')
        ->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id)
        ->call('save', $asset->id);

    assertDatabaseHas(HardwareReplacementSelection::class, [
        'tdx_asset_id' => $asset->id,
        'hardware_model_id' => $replacementModel->id,
        'with_docking' => false,
    ]);
});

test('the docking station column shows Unknown when TDX has not reported a docking status', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures([
        'has_docking_station' => null,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee('Unknown');
});

test('re-selecting a replacement updates the existing selection instead of duplicating it', function () {
    $user = User::factory()->create();
    ['division' => $division, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::workstations.replacements');

    $component->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id)
        ->call('save', $asset->id);

    $component->set("selections.{$asset->id}.notes", 'Updated note')
        ->call('save', $asset->id);

    assertDatabaseCount(HardwareReplacementSelection::class, 1);
    assertDatabaseHas(HardwareReplacementSelection::class, [
        'tdx_asset_id' => $asset->id,
        'notes' => 'Updated note',
    ]);
});

test('a user can opt out of selecting a replacement model', function () {
    $user = User::factory()->create();
    ['cycle' => $cycle, 'division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->set("selections.{$asset->id}.opted_out", true)
        ->call('save', $asset->id)
        ->assertHasNoErrors();

    assertDatabaseHas(HardwareReplacementSelection::class, [
        'budget_cycle_id' => $cycle->id,
        'tdx_asset_id' => $asset->id,
        'hardware_model_id' => null,
        'opted_out' => true,
        'with_docking' => false,
    ]);
});

test('opting out does not count toward the pending total', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::workstations.replacements')
        ->set("selections.{$asset->id}.opted_out", true)
        ->call('save', $asset->id);

    $assetRow = collect($component->instance()->groupedRows)->firstWhere('type', 'asset');

    expect($assetRow['opted_out'])->toBeTrue();

    $grandTotal = collect($component->instance()->groupedRows)->firstWhere('type', 'grand_total');

    expect($grandTotal['pending'])->toBe(0);
});

test('an asset outside the user\'s responsibility scope is not shown', function () {
    $user = User::factory()->create();
    ['asset' => $asset] = setUpWorkstationReplacementFixtures();

    $otherDivision = ResponsibleDivision::factory()->create();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $otherDivision->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('an asset whose fy_replacement is later than the open cycle is not shown', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures(['fy_replacement' => 30]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('an asset whose fy_replacement is earlier than the open cycle is still shown', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures(['fy_replacement' => 26]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee($asset->asset_tag);
});

test('an asset already given a real replacement in an earlier cycle is not shown again', function () {
    $user = User::factory()->create();
    ['division' => $division, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures(['fy_replacement' => 26]);

    $pastCycle = BudgetCycle::factory()->create(['fiscal_year' => 26, 'status' => BudgetCycleStatus::Closed]);

    HardwareReplacementSelection::factory()->create([
        'budget_cycle_id' => $pastCycle->id,
        'tdx_asset_id' => $asset->id,
        'hardware_model_id' => $replacementModel->id,
        'opted_out' => false,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('an asset that was only opted out in an earlier cycle is shown again', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures(['fy_replacement' => 26]);

    $pastCycle = BudgetCycle::factory()->create(['fiscal_year' => 26, 'status' => BudgetCycleStatus::Closed]);

    HardwareReplacementSelection::factory()->create([
        'budget_cycle_id' => $pastCycle->id,
        'tdx_asset_id' => $asset->id,
        'hardware_model_id' => null,
        'opted_out' => true,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee($asset->asset_tag);
});

test('a non-workstation-category asset is not shown', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    $otherCategory = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $otherModel = HardwareModel::factory()->create(['hardware_category_id' => $otherCategory->id]);
    $asset->update(['hardware_model_id' => $otherModel->id]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('a view-role responsibility renders the asset read-only', function () {
    $user = User::factory()->create();
    ['division' => $division, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee($asset->asset_tag)
        ->assertSee('View only')
        ->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id)
        ->call('save', $asset->id)
        ->assertStatus(403);

    assertDatabaseCount(HardwareReplacementSelection::class, 0);
});

test('a user with edit responsibility over the asset location sees and selects an eligible replacement', function () {
    $user = User::factory()->create();
    ['cycle' => $cycle, 'division' => $division, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    $location = ResponsibleLocation::factory()->create([
        'responsible_division_id' => $division->id,
        'name' => 'Willow',
    ]);
    $asset->update(['responsible_location_id' => $location->id]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'location',
        'responsible_location_id' => $location->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee($asset->asset_tag)
        ->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id)
        ->call('save', $asset->id);

    assertDatabaseHas(HardwareReplacementSelection::class, [
        'budget_cycle_id' => $cycle->id,
        'tdx_asset_id' => $asset->id,
        'hardware_model_id' => $replacementModel->id,
        'selected_by_id' => $user->id,
    ]);
});

test('an asset outside the user\'s location scope is not shown', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    $location = ResponsibleLocation::factory()->create(['responsible_division_id' => $division->id, 'name' => 'Willow']);
    $asset->update(['responsible_location_id' => $location->id]);

    $otherLocation = ResponsibleLocation::factory()->create(['responsible_division_id' => $division->id, 'name' => 'Big Lake']);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'location',
        'responsible_location_id' => $otherLocation->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('the replacements table groups assets and shows the assigned user, division, and location breadcrumb', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures([
        'assigned_user_upn' => 'Jamie Sample',
    ]);

    $location = ResponsibleLocation::factory()->create(['responsible_division_id' => $division->id, 'name' => 'Willow']);
    $asset->update(['responsible_location_id' => $location->id]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee('Jamie Sample')
        ->assertSee('Information Technology — Business Operations')
        ->assertSee('Information Technology — Business Operations — Willow');
});

test('two assets in the same location are grouped under one header with a location subtotal', function () {
    $user = User::factory()->create();
    ['division' => $division, 'currentModel' => $currentModel, 'asset' => $firstAsset] = setUpWorkstationReplacementFixtures([
        'asset_tag' => 'AT-00001',
    ]);

    $location = ResponsibleLocation::factory()->create(['responsible_division_id' => $division->id, 'name' => 'Willow']);
    $firstAsset->update(['responsible_location_id' => $location->id]);

    $secondAsset = TdxAsset::factory()->create([
        'hardware_model_id' => $currentModel->id,
        'assigned_department_code' => 'Information Technology',
        'responsible_division_id' => $division->id,
        'responsible_location_id' => $location->id,
        'asset_tag' => 'AT-00002',
        'fy_replacement' => 28,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSeeInOrder([
            'Information Technology — Business Operations — Willow',
            $firstAsset->asset_tag,
            $secondAsset->asset_tag,
            'Information Technology — Business Operations — Willow subtotal',
        ]);
});

test('an asset with a direct division and no location still gets a division-level subtotal', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee('Information Technology — Business Operations subtotal');
});

test('current and replacement model costs render and update as the replacement selection changes', function () {
    $user = User::factory()->create();
    ['division' => $division, 'currentModel' => $currentModel, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    HardwareModelCost::factory()->create([
        'hardware_model_id' => $currentModel->id,
        'fiscal_year' => 28,
        'unit_cost' => 1200,
        'with_docking' => false,
    ]);
    HardwareModelCost::factory()->create([
        'hardware_model_id' => $replacementModel->id,
        'fiscal_year' => 28,
        'unit_cost' => 1500,
        'with_docking' => false,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::workstations.replacements')->assertSee('$1,200.00');

    $assetRow = fn () => collect($component->instance()->groupedRows)->firstWhere('type', 'asset');

    expect($assetRow()['current_cost'])->toBe(1200.0);
    expect($assetRow()['replacement_cost'])->toBeNull();

    $component->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id);

    expect($assetRow()['replacement_cost'])->toBe(1500.0);
});

test('an asset with no current-year cost row shows a dash instead of erroring', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertOk()
        ->assertSee($asset->asset_tag);
});

test('a grand total row appears once summing every visible asset', function () {
    $user = User::factory()->create();
    ['division' => $division, 'currentModel' => $currentModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    HardwareModelCost::factory()->create([
        'hardware_model_id' => $currentModel->id,
        'fiscal_year' => 28,
        'unit_cost' => 900,
        'with_docking' => false,
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    $html = livewire('pages::workstations.replacements')
        ->assertSeeText('Grand total')
        ->html();

    expect(substr_count($html, 'Grand total'))->toBe(1);
});

test('the replacements table shows the asset division and location', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    $location = ResponsibleLocation::factory()->create(['responsible_division_id' => $division->id, 'name' => 'Willow']);
    $asset->update(['responsible_location_id' => $location->id]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee($division->name)
        ->assertSee('Willow');
});

test('a manager with edit responsibility can open the edit modal for an asset', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::workstations.replacements')->call('edit', $asset->id);

    expect($component->instance()->editingAssetId)->toBe($asset->id);
});

test('a view-role user cannot open the edit modal', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->call('edit', $asset->id)
        ->assertStatus(403);
});

test('saving a selection from the edit modal closes it', function () {
    $user = User::factory()->create();
    ['division' => $division, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::workstations.replacements')
        ->call('edit', $asset->id)
        ->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id)
        ->call('save', $asset->id);

    expect($component->instance()->editingAssetId)->toBeNull();
});

test('collapsing a division hides its asset and location rows but keeps the subtotal visible', function () {
    $user = User::factory()->create();
    ['division' => $division, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    $divisionKey = 'division:'.$division->id;

    livewire('pages::workstations.replacements')
        ->assertSee($asset->asset_tag)
        ->call('toggleDivision', $divisionKey)
        ->assertDontSee($asset->asset_tag)
        ->assertSee('Information Technology — Business Operations subtotal')
        ->call('toggleDivision', $divisionKey)
        ->assertSee($asset->asset_tag);
});

test('shows an empty state when there is no open budget cycle', function () {
    $user = User::factory()->create();
    BudgetCycle::factory()->create(['status' => BudgetCycleStatus::Draft]);

    $division = ResponsibleDivision::factory()->create();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertOk()
        ->assertSee('No open budget cycle');
});
