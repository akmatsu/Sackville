<?php

use App\Enums\BudgetLineItemStatus;
use App\Filament\Resources\BudgetLineItems\Pages\CreateBudgetLineItem;
use App\Filament\Resources\BudgetLineItems\Pages\EditBudgetLineItem;
use App\Filament\Resources\BudgetLineItems\Pages\ListBudgetLineItems;
use App\Filament\Resources\BudgetLineItems\RelationManagers\GlAllocationsRelationManager;
use App\Models\BudgetCycle;
use App\Models\BudgetLineItem;
use App\Models\GlCode;
use App\Models\HardwareModel;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('lists budget line items', function () {
    $lineItems = BudgetLineItem::factory()->count(3)->create();

    livewire(ListBudgetLineItems::class)
        ->assertCanSeeTableRecords($lineItems);
});

it('creates a budget line item and stamps the creating user', function () {
    $cycle = BudgetCycle::factory()->create();
    $hardwareModel = HardwareModel::factory()->create();

    livewire(CreateBudgetLineItem::class)
        ->fillForm([
            'budget_cycle_id' => $cycle->id,
            'item_type' => 'hardware_replacement',
            'hardware_model_id' => $hardwareModel->id,
            'quantity' => 2,
            'proposed_cost' => 1500,
            'description' => 'Replace aging laptops',
            'status' => 'not_started',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(BudgetLineItem::class, [
        'budget_cycle_id' => $cycle->id,
        'hardware_model_id' => $hardwareModel->id,
        'description' => 'Replace aging laptops',
        'created_by_id' => $this->user->id,
    ]);
});

it('requires a budget cycle and item type to create a budget line item', function () {
    livewire(CreateBudgetLineItem::class)
        ->fillForm(['budget_cycle_id' => null, 'item_type' => null])
        ->call('create')
        ->assertHasFormErrors(['budget_cycle_id' => 'required', 'item_type' => 'required']);
});

it('updates a budget line item and stamps the modifying user', function () {
    $lineItem = BudgetLineItem::factory()->create();
    $editor = User::factory()->create();
    $this->actingAs($editor);

    livewire(EditBudgetLineItem::class, ['record' => $lineItem->getKey()])
        ->fillForm(['status' => 'complete'])
        ->call('save')
        ->assertHasNoFormErrors();

    $lineItem->refresh();

    expect($lineItem->status)->toBe(BudgetLineItemStatus::Complete);
    expect($lineItem->last_modified_by_id)->toBe($editor->id);
});

it('manages gl allocations for a budget line item', function () {
    $lineItem = BudgetLineItem::factory()->create();
    $glCode = GlCode::factory()->create();

    livewire(GlAllocationsRelationManager::class, [
        'ownerRecord' => $lineItem,
        'pageClass' => EditBudgetLineItem::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'gl_code_id' => $glCode->id,
            'percent' => 100,
            'amount' => 1500,
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas('line_item_gl_allocations', [
        'budget_line_item_id' => $lineItem->id,
        'gl_code_id' => $glCode->id,
        'percent' => 100,
    ]);
});
