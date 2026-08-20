<?php

use App\Enums\BudgetCycleStatus;
use App\Models\BudgetCycle;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareReplacementGroup;
use App\Models\HardwareReplacementSelection;
use App\Models\PublicWifiCircuitReview;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\TdxAsset;
use App\Models\TdxPublicWifiCircuit;
use App\Models\User;

use function Pest\Livewire\livewire;

test('the budget cycle widget shows the open cycle', function () {
    BudgetCycle::factory()->create(['fiscal_year' => 28, 'status' => BudgetCycleStatus::Open]);

    $this->actingAs(User::factory()->create());

    livewire('dashboard.budget-cycle-status')
        ->assertSee('FY28')
        ->assertSee('Open');
});

test('the budget cycle widget shows an upcoming draft cycle as coming soon when there is no open cycle', function () {
    BudgetCycle::factory()->create([
        'fiscal_year' => 29,
        'status' => BudgetCycleStatus::Draft,
        'opens_at' => now()->addMonth(),
    ]);

    $this->actingAs(User::factory()->create());

    livewire('dashboard.budget-cycle-status')
        ->assertSee('FY29')
        ->assertSee('Coming soon');
});

test('the budget cycle widget falls back to the most recently closed cycle', function () {
    BudgetCycle::factory()->create(['fiscal_year' => 26, 'status' => BudgetCycleStatus::Closed]);
    BudgetCycle::factory()->create(['fiscal_year' => 27, 'status' => BudgetCycleStatus::Closed]);

    $this->actingAs(User::factory()->create());

    livewire('dashboard.budget-cycle-status')
        ->assertSee('FY27')
        ->assertSee('Closed');
});

test('the budget cycle widget shows an empty state when no cycle exists', function () {
    $this->actingAs(User::factory()->create());

    livewire('dashboard.budget-cycle-status')
        ->assertSee('No budget cycle has been configured.');
});

test('the workstation widget counts assets eligible for and selected in the open cycle', function () {
    $cycle = BudgetCycle::factory()->create(['fiscal_year' => 28, 'status' => BudgetCycleStatus::Open]);
    $division = ResponsibleDivision::factory()->create();

    $category = HardwareCategory::factory()->create(['name' => 'Workstation']);
    $currentModel = HardwareModel::factory()->create(['hardware_category_id' => $category->id]);
    $replacementModel = HardwareModel::factory()->create(['hardware_category_id' => $category->id, 'active' => true]);

    $group = HardwareReplacementGroup::factory()->create(['active' => true]);
    $group->replaceableCategories()->attach($category);
    $group->eligibleModels()->attach($replacementModel);

    $selectedAsset = TdxAsset::factory()->create([
        'hardware_model_id' => $currentModel->id,
        'responsible_division_id' => $division->id,
        'fy_replacement' => 28,
    ]);
    HardwareReplacementSelection::factory()->create([
        'budget_cycle_id' => $cycle->id,
        'tdx_asset_id' => $selectedAsset->id,
        'hardware_model_id' => $replacementModel->id,
        'opted_out' => false,
    ]);

    TdxAsset::factory()->create([
        'hardware_model_id' => $currentModel->id,
        'responsible_division_id' => $division->id,
        'fy_replacement' => 28,
    ]);

    $user = User::factory()->create();
    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    $component = livewire('dashboard.workstation-replacement-status');

    expect($component->instance()->eligibleCount)->toBe(2);
    expect($component->instance()->selectedCount)->toBe(1);
});

test('the workstation widget only counts assets visible to the user', function () {
    BudgetCycle::factory()->create(['fiscal_year' => 28, 'status' => BudgetCycleStatus::Open]);
    $division = ResponsibleDivision::factory()->create();
    $otherDivision = ResponsibleDivision::factory()->create();

    $category = HardwareCategory::factory()->create(['name' => 'Workstation']);
    $currentModel = HardwareModel::factory()->create(['hardware_category_id' => $category->id]);

    TdxAsset::factory()->create([
        'hardware_model_id' => $currentModel->id,
        'responsible_division_id' => $otherDivision->id,
        'fy_replacement' => 28,
    ]);

    $user = User::factory()->create();
    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    $component = livewire('dashboard.workstation-replacement-status');

    expect($component->instance()->eligibleCount)->toBe(0);
});

test('the workstation widget shows an empty state when there is no open budget cycle', function () {
    $this->actingAs(User::factory()->create());

    livewire('dashboard.workstation-replacement-status')
        ->assertSee('No open budget cycle.');
});

test('the workstation widget links to the replacements page', function () {
    $this->actingAs(User::factory()->create());

    livewire('dashboard.workstation-replacement-status')
        ->assertSeeHtml(route('workstations.replacements'));
});

test('the budget cycle widget reports days remaining and elapsed progress for an open cycle', function () {
    BudgetCycle::factory()->create([
        'fiscal_year' => 28,
        'status' => BudgetCycleStatus::Open,
        'opens_at' => now()->subDays(10),
        'closes_at' => now()->addDays(20),
    ]);

    $this->actingAs(User::factory()->create());

    $component = livewire('dashboard.budget-cycle-status');

    expect($component->instance()->daysRemaining)->toBe(20);
    expect($component->instance()->progressRatio)->toEqualWithDelta(10 / 30, 0.01);
});

test('the budget cycle widget counts down to an upcoming draft cycle with an empty ring', function () {
    BudgetCycle::factory()->create([
        'fiscal_year' => 29,
        'status' => BudgetCycleStatus::Draft,
        'opens_at' => now()->addDays(14),
        'closes_at' => now()->addDays(104),
    ]);

    $this->actingAs(User::factory()->create());

    $component = livewire('dashboard.budget-cycle-status');

    expect($component->instance()->daysRemaining)->toBe(14);
    expect($component->instance()->progressRatio)->toBe(0.0);
});

test('the budget cycle widget shows a full ring for a closed cycle', function () {
    BudgetCycle::factory()->create([
        'fiscal_year' => 26,
        'status' => BudgetCycleStatus::Closed,
        'opens_at' => now()->subDays(100),
        'closes_at' => now()->subDays(10),
    ]);

    $this->actingAs(User::factory()->create());

    $component = livewire('dashboard.budget-cycle-status');

    expect($component->instance()->progressRatio)->toBe(1.0);
});

test('the public wifi widget counts circuits eligible for and reviewed in the open cycle', function () {
    $cycle = BudgetCycle::factory()->create(['fiscal_year' => 28, 'status' => BudgetCycleStatus::Open]);
    $division = ResponsibleDivision::factory()->create();

    $reviewedCircuit = TdxPublicWifiCircuit::factory()->create([
        'responsible_division_id' => $division->id,
        'status' => 'Active',
    ]);
    PublicWifiCircuitReview::factory()->create([
        'budget_cycle_id' => $cycle->id,
        'tdx_public_wifi_circuit_id' => $reviewedCircuit->id,
        'still_needed' => true,
        'justification' => 'Still needed.',
    ]);

    TdxPublicWifiCircuit::factory()->create([
        'responsible_division_id' => $division->id,
        'status' => 'Active',
    ]);

    $user = User::factory()->create();
    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    $component = livewire('dashboard.public-wifi-review-status');

    expect($component->instance()->eligibleCount)->toBe(2);
    expect($component->instance()->reviewedCount)->toBe(1);
});

test('the public wifi widget only counts circuits visible to the user', function () {
    BudgetCycle::factory()->create(['fiscal_year' => 28, 'status' => BudgetCycleStatus::Open]);
    $division = ResponsibleDivision::factory()->create();
    $otherDivision = ResponsibleDivision::factory()->create();

    TdxPublicWifiCircuit::factory()->create([
        'responsible_division_id' => $otherDivision->id,
        'status' => 'Active',
    ]);

    $user = User::factory()->create();
    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    $component = livewire('dashboard.public-wifi-review-status');

    expect($component->instance()->eligibleCount)->toBe(0);
});

test('the public wifi widget excludes surplus circuits', function () {
    BudgetCycle::factory()->create(['fiscal_year' => 28, 'status' => BudgetCycleStatus::Open]);
    $division = ResponsibleDivision::factory()->create();

    TdxPublicWifiCircuit::factory()->create([
        'responsible_division_id' => $division->id,
        'status' => 'Surplus',
    ]);

    $user = User::factory()->create();
    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    $component = livewire('dashboard.public-wifi-review-status');

    expect($component->instance()->eligibleCount)->toBe(0);
});

test('the public wifi widget shows an empty state when there is no open budget cycle', function () {
    $this->actingAs(User::factory()->create());

    livewire('dashboard.public-wifi-review-status')
        ->assertSee('No open budget cycle.');
});

test('the public wifi widget links to the reviews page', function () {
    $this->actingAs(User::factory()->create());

    livewire('dashboard.public-wifi-review-status')
        ->assertSeeHtml(route('public-wifi.reviews'));
});
