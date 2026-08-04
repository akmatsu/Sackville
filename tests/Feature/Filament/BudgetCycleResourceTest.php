<?php

use App\Enums\BudgetCycleStatus;
use App\Filament\Resources\BudgetCycles\Pages\CreateBudgetCycle;
use App\Filament\Resources\BudgetCycles\Pages\EditBudgetCycle;
use App\Filament\Resources\BudgetCycles\Pages\ListBudgetCycles;
use App\Filament\Resources\BudgetCycles\RelationManagers\LineItemsRelationManager;
use App\Models\BudgetCycle;
use App\Models\BudgetLineItem;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists budget cycles', function () {
    $cycles = BudgetCycle::factory()->count(3)->create();

    livewire(ListBudgetCycles::class)
        ->assertCanSeeTableRecords($cycles);
});

it('creates a budget cycle', function () {
    livewire(CreateBudgetCycle::class)
        ->fillForm([
            'fiscal_year' => 29,
            'opens_at' => '2028-07-01',
            'closes_at' => '2028-09-30',
            'status' => 'draft',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(BudgetCycle::class, ['fiscal_year' => 29, 'status' => 'draft']);
});

it('requires a unique fiscal year to create a budget cycle', function () {
    BudgetCycle::factory()->create(['fiscal_year' => 29]);

    livewire(CreateBudgetCycle::class)
        ->fillForm([
            'fiscal_year' => 29,
            'opens_at' => '2028-07-01',
            'closes_at' => '2028-09-30',
            'status' => 'draft',
        ])
        ->call('create')
        ->assertHasFormErrors(['fiscal_year' => 'unique']);
});

it('updates a budget cycle', function () {
    $cycle = BudgetCycle::factory()->create();

    livewire(EditBudgetCycle::class, ['record' => $cycle->getKey()])
        ->fillForm(['status' => 'open'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($cycle->refresh()->status)->toBe(BudgetCycleStatus::Open);
});

it('shows the line items belonging to a budget cycle', function () {
    $cycle = BudgetCycle::factory()->create();
    $lineItems = BudgetLineItem::factory()->count(2)->create(['budget_cycle_id' => $cycle->id]);
    BudgetLineItem::factory()->create();

    livewire(LineItemsRelationManager::class, [
        'ownerRecord' => $cycle,
        'pageClass' => EditBudgetCycle::class,
    ])
        ->assertCanSeeTableRecords($lineItems);
});
