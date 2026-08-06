<?php

use App\Enums\BudgetCycleStatus;
use App\Models\BudgetCycle;
use App\Models\Division;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareReplacementGroup;
use App\Models\HardwareReplacementSelection;
use App\Models\Responsibility;
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

    $division = Division::factory()->create(['code' => '117']);

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
        'assigned_division_id' => $division->id,
        'fy_replacement' => 28,
    ], $assetOverrides));

    return compact('cycle', 'division', 'category', 'currentModel', 'replacementModel', 'group', 'asset');
}

test('a user with edit responsibility over the asset scope sees and selects an eligible replacement', function () {
    $user = User::factory()->create();
    ['cycle' => $cycle, 'replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => '117',
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertSee($asset->asset_tag)
        ->set("selections.{$asset->id}.hardware_model_id", $replacementModel->id)
        ->set("selections.{$asset->id}.with_docking", true)
        ->call('save', $asset->id);

    assertDatabaseHas(HardwareReplacementSelection::class, [
        'budget_cycle_id' => $cycle->id,
        'tdx_asset_id' => $asset->id,
        'hardware_model_id' => $replacementModel->id,
        'with_docking' => true,
        'selected_by_id' => $user->id,
    ]);
});

test('re-selecting a replacement updates the existing selection instead of duplicating it', function () {
    $user = User::factory()->create();
    ['replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => '117',
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

test('an asset outside the user\'s responsibility scope is not shown', function () {
    $user = User::factory()->create();
    ['asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => 'some-other-division',
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('an asset whose fy_replacement does not match the open cycle is not shown', function () {
    $user = User::factory()->create();
    ['asset' => $asset] = setUpWorkstationReplacementFixtures(['fy_replacement' => 30]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => '117',
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('a non-workstation-category asset is not shown', function () {
    $user = User::factory()->create();
    ['asset' => $asset] = setUpWorkstationReplacementFixtures();

    $otherCategory = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $otherModel = HardwareModel::factory()->create(['hardware_category_id' => $otherCategory->id]);
    $asset->update(['hardware_model_id' => $otherModel->id]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => '117',
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee($asset->asset_tag);
});

test('a view-role responsibility renders the asset read-only', function () {
    $user = User::factory()->create();
    ['replacementModel' => $replacementModel, 'asset' => $asset] = setUpWorkstationReplacementFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => '117',
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

test('shows an empty state when there is no open budget cycle', function () {
    $user = User::factory()->create();
    BudgetCycle::factory()->create(['status' => BudgetCycleStatus::Draft]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => '117',
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertOk()
        ->assertSee('No open budget cycle');
});
