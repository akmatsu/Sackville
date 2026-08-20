<?php

use App\Enums\BudgetCycleStatus;
use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use App\Models\BudgetCycle;
use App\Models\BudgetLineItem;
use App\Models\GlCode;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use App\Models\HardwareReplacementGroup;
use App\Models\LineItemGlAllocation;
use App\Models\ObjectCode;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\SubObjectCode;
use App\Models\TdxAsset;
use App\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function setUpWorkstationNewRequestFixtures(): array
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
    $model = HardwareModel::factory()->create(['hardware_category_id' => $category->id, 'active' => true]);

    $group = HardwareReplacementGroup::factory()->create(['active' => true]);
    $group->replaceableCategories()->attach($category);
    $group->eligibleModels()->attach($model);

    // An existing asset in the division so requestableDivisions() and the
    // GL resolver both have something to key off of.
    $existingAsset = TdxAsset::factory()->create([
        'hardware_model_id' => $model->id,
        'assigned_department_code' => 'Information Technology',
        'responsible_division_id' => $division->id,
        'fy_replacement' => 30,
    ]);

    HardwareModelCost::factory()->create([
        'hardware_model_id' => $model->id,
        'fiscal_year' => 28,
        'unit_cost' => 1200,
        'with_docking' => false,
    ]);

    return compact('cycle', 'division', 'category', 'model', 'existingAsset');
}

function actingAsWorkstationEditor(ResponsibleDivision $division): User
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

test('a user with edit responsibility can submit a new asset request', function () {
    ['division' => $division, 'model' => $model, 'cycle' => $cycle] = setUpWorkstationNewRequestFixtures();
    $user = actingAsWorkstationEditor($division);

    livewire('pages::workstations.replacements')
        ->call('openNewRequest')
        ->set('newRequest.responsible_division_id', $division->id)
        ->set('newRequest.hardware_model_id', $model->id)
        ->set('newRequest.quantity', 2)
        ->set('newRequest.justification', 'New hire starting next month.')
        ->call('saveRequest')
        ->assertHasNoErrors();

    assertDatabaseHas(BudgetLineItem::class, [
        'budget_cycle_id' => $cycle->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::HardwareAddition->value,
        'hardware_model_id' => $model->id,
        'quantity' => 2,
        'proposed_cost' => 2400,
        'justification' => 'New hire starting next month.',
        'status' => BudgetLineItemStatus::NotStarted->value,
        'created_by_id' => $user->id,
    ]);
});

test('a user without edit responsibility over any division cannot see or submit the request form', function () {
    setUpWorkstationNewRequestFixtures();
    $user = User::factory()->create();
    test()->actingAs($user);

    livewire('pages::workstations.replacements')
        ->assertDontSee('Request new asset')
        ->call('openNewRequest')
        ->assertStatus(403);

    assertDatabaseCount(BudgetLineItem::class, 0);
});

test('the gl code auto-resolves when the division has an existing gl-coded asset and the category has defaults', function () {
    ['division' => $division, 'model' => $model, 'category' => $category, 'existingAsset' => $existingAsset] = setUpWorkstationNewRequestFixtures();
    actingAsWorkstationEditor($division);

    $referenceGlCode = GlCode::factory()->create();
    $existingAsset->update(['gl_code_id' => $referenceGlCode->id]);

    $objectCode = ObjectCode::factory()->create();
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);
    $category->update([
        'default_object_code' => $objectCode->code,
        'default_sub_object_code_id' => $subObjectCode->id,
    ]);

    $targetGlCode = GlCode::factory()->create([
        'fund_code' => $referenceGlCode->fund_code,
        'department_code' => $referenceGlCode->department_code,
        'division_id' => $referenceGlCode->division_id,
        'object_code' => $objectCode->code,
        'sub_object_code_id' => $subObjectCode->id,
    ]);

    livewire('pages::workstations.replacements')
        ->call('openNewRequest')
        ->set('newRequest.responsible_division_id', $division->id)
        ->set('newRequest.hardware_model_id', $model->id)
        ->set('newRequest.quantity', 1)
        ->set('newRequest.justification', 'Additional workstation needed.')
        ->call('saveRequest');

    $item = BudgetLineItem::query()->where('item_type', BudgetLineItemType::HardwareAddition)->firstOrFail();

    assertDatabaseHas(LineItemGlAllocation::class, [
        'budget_line_item_id' => $item->id,
        'gl_code_id' => $targetGlCode->id,
        'percent' => 100,
    ]);
});

test('the gl code is left pending when it cannot be resolved', function () {
    ['division' => $division, 'model' => $model] = setUpWorkstationNewRequestFixtures();
    actingAsWorkstationEditor($division);

    // No default_object_code/default_sub_object_code_id configured, and the
    // existing asset in the division has no gl_code_id assigned.
    livewire('pages::workstations.replacements')
        ->call('openNewRequest')
        ->set('newRequest.responsible_division_id', $division->id)
        ->set('newRequest.hardware_model_id', $model->id)
        ->set('newRequest.quantity', 1)
        ->set('newRequest.justification', 'Additional workstation needed.')
        ->call('saveRequest');

    $item = BudgetLineItem::query()->where('item_type', BudgetLineItemType::HardwareAddition)->firstOrFail();

    assertDatabaseCount(LineItemGlAllocation::class, 0);
    expect($item->glAllocations)->toBeEmpty();
});

test('another user within the same responsibility scope can see but not edit the request', function () {
    ['division' => $division, 'model' => $model] = setUpWorkstationNewRequestFixtures();
    $requester = actingAsWorkstationEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::HardwareAddition,
        'hardware_model_id' => $model->id,
        'quantity' => 1,
        'proposed_cost' => 1200,
        'justification' => 'Growth hire.',
        'status' => BudgetLineItemStatus::NotStarted,
        'created_by_id' => $requester->id,
    ]);

    $otherUser = actingAsWorkstationEditor($division);

    livewire('pages::workstations.replacements')
        ->assertSee('Growth hire.')
        ->assertSee('View only')
        ->call('editRequest', $item->id)
        ->assertStatus(403);
});

test('the requester can edit their own not-started request', function () {
    ['division' => $division, 'model' => $model] = setUpWorkstationNewRequestFixtures();
    $requester = actingAsWorkstationEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::HardwareAddition,
        'hardware_model_id' => $model->id,
        'quantity' => 1,
        'proposed_cost' => 1200,
        'justification' => 'Growth hire.',
        'status' => BudgetLineItemStatus::NotStarted,
        'created_by_id' => $requester->id,
    ]);

    livewire('pages::workstations.replacements')
        ->call('editRequest', $item->id)
        ->set('newRequest.quantity', 3)
        ->set('newRequest.justification', 'Growth hire - updated headcount.')
        ->call('saveRequest')
        ->assertHasNoErrors();

    assertDatabaseHas(BudgetLineItem::class, [
        'id' => $item->id,
        'quantity' => 3,
        'justification' => 'Growth hire - updated headcount.',
    ]);
});

test('a request that is no longer not-started cannot be edited or deleted by the requester', function () {
    ['division' => $division, 'model' => $model] = setUpWorkstationNewRequestFixtures();
    $requester = actingAsWorkstationEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::HardwareAddition,
        'hardware_model_id' => $model->id,
        'quantity' => 1,
        'proposed_cost' => 1200,
        'justification' => 'Growth hire.',
        'status' => BudgetLineItemStatus::InProgress,
        'created_by_id' => $requester->id,
    ]);

    livewire('pages::workstations.replacements')
        ->call('editRequest', $item->id)
        ->assertStatus(403);

    livewire('pages::workstations.replacements')
        ->call('deleteRequest', $item->id)
        ->assertStatus(403);
});

test('the requester can delete their own not-started request', function () {
    ['division' => $division, 'model' => $model] = setUpWorkstationNewRequestFixtures();
    $requester = actingAsWorkstationEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::HardwareAddition,
        'hardware_model_id' => $model->id,
        'quantity' => 1,
        'proposed_cost' => 1200,
        'justification' => 'Growth hire.',
        'status' => BudgetLineItemStatus::NotStarted,
        'created_by_id' => $requester->id,
    ]);

    livewire('pages::workstations.replacements')
        ->call('deleteRequest', $item->id)
        ->assertHasNoErrors();

    assertDatabaseCount(BudgetLineItem::class, 0);
});
