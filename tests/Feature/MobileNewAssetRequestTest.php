<?php

use App\Enums\BudgetCycleStatus;
use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use App\Models\BudgetCycle;
use App\Models\BudgetLineItem;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use App\Models\HardwareReplacementGroup;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\TdxAsset;
use App\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function setUpMobileNewRequestFixtures(): array
{
    $cycle = BudgetCycle::factory()->create([
        'fiscal_year' => 28,
        'status' => BudgetCycleStatus::Open,
    ]);

    $division = ResponsibleDivision::factory()->create([
        'department_name' => 'Information Technology',
        'name' => 'Business Operations',
    ]);

    $category = HardwareCategory::factory()->create(['name' => 'Mobile']);
    $model = HardwareModel::factory()->create(['hardware_category_id' => $category->id, 'active' => true]);

    $group = HardwareReplacementGroup::factory()->create(['active' => true]);
    $group->replaceableCategories()->attach($category);
    $group->eligibleModels()->attach($model);

    $existingAsset = TdxAsset::factory()->create([
        'hardware_model_id' => $model->id,
        'assigned_department_code' => 'Information Technology',
        'responsible_division_id' => $division->id,
        'fy_replacement' => 30,
    ]);

    HardwareModelCost::factory()->create([
        'hardware_model_id' => $model->id,
        'fiscal_year' => 28,
        'unit_cost' => 800,
        'with_docking' => false,
    ]);

    return compact('cycle', 'division', 'category', 'model', 'existingAsset');
}

function actingAsMobileEditor(ResponsibleDivision $division): User
{
    $user = User::factory()->create();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    test()->actingAs($user);

    return $user;
}

test('a user with edit responsibility can submit a new mobile device request', function () {
    ['division' => $division, 'model' => $model, 'cycle' => $cycle] = setUpMobileNewRequestFixtures();
    $user = actingAsMobileEditor($division);

    livewire('pages::mobile.replacements')
        ->call('openNewRequest')
        ->set('newRequest.responsible_division_id', $division->id)
        ->set('newRequest.hardware_model_id', $model->id)
        ->set('newRequest.quantity', 1)
        ->set('newRequest.justification', 'New field technician position.')
        ->call('saveRequest')
        ->assertHasNoErrors();

    assertDatabaseHas(BudgetLineItem::class, [
        'budget_cycle_id' => $cycle->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::HardwareAddition->value,
        'hardware_model_id' => $model->id,
        'with_docking' => false,
        'quantity' => 1,
        'proposed_cost' => 800,
        'status' => BudgetLineItemStatus::NotStarted->value,
        'created_by_id' => $user->id,
    ]);
});

test('a user without edit responsibility over any division cannot see or submit the mobile request form', function () {
    setUpMobileNewRequestFixtures();
    $user = User::factory()->create();
    test()->actingAs($user);

    livewire('pages::mobile.replacements')
        ->assertDontSee('Request new asset')
        ->call('openNewRequest')
        ->assertStatus(403);

    assertDatabaseCount(BudgetLineItem::class, 0);
});
