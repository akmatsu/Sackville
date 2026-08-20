<?php

use App\Enums\ResponsibilityScopeType;
use App\Models\BudgetLineItem;
use App\Models\GlCode;
use App\Models\LineItemGlAllocation;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\User;

test('a division-scoped responsibility sees line items assigned to that division', function () {
    $user = User::factory()->create();
    $division = ResponsibleDivision::factory()->create();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $item = BudgetLineItem::factory()->create(['responsible_division_id' => $division->id]);
    BudgetLineItem::factory()->create(['responsible_division_id' => ResponsibleDivision::factory()->create()->id]);

    $visible = BudgetLineItem::query()->visibleTo($user)->get();

    expect($visible->pluck('id')->all())->toBe([$item->id]);
});

test('a location-scoped responsibility never sees hardware-addition line items', function () {
    $user = User::factory()->create();
    $location = ResponsibleLocation::factory()->create();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'location',
        'responsible_location_id' => $location->id,
        'role' => 'view',
    ]);

    BudgetLineItem::factory()->create();

    expect(BudgetLineItem::query()->visibleTo($user)->count())->toBe(0);
});

test('a fund-scoped responsibility sees line items through their gl allocation', function () {
    $user = User::factory()->create();
    $glCode = GlCode::factory()->create();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'fund',
        'scope_value' => $glCode->fund_code,
        'role' => 'view',
    ]);

    $item = BudgetLineItem::factory()->create();
    LineItemGlAllocation::factory()->create([
        'budget_line_item_id' => $item->id,
        'gl_code_id' => $glCode->id,
        'percent' => 100,
        'amount' => 100,
    ]);

    $unallocated = BudgetLineItem::factory()->create();

    $visible = BudgetLineItem::query()->visibleTo($user)->get();

    expect($visible->pluck('id')->all())->toBe([$item->id])
        ->and($visible->pluck('id'))->not->toContain($unallocated->id);
});

dataset('nullable-value scope types', [
    'Department' => [ResponsibilityScopeType::Department],
    'Fund' => [ResponsibilityScopeType::Fund],
    'Object' => [ResponsibilityScopeType::Object],
    'SpecificGl' => [ResponsibilityScopeType::SpecificGl],
]);

test('scopeVisibleTo() does not surface line items when scope_value is null', function (ResponsibilityScopeType $scopeType) {
    $user = User::factory()->create();
    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => $scopeType,
        'scope_value' => null,
    ]);

    BudgetLineItem::factory()->create();

    expect(BudgetLineItem::query()->visibleTo($user)->count())->toBe(0);
})->with('nullable-value scope types');

test('a user with no responsibilities sees no line items', function () {
    $user = User::factory()->create();

    BudgetLineItem::factory()->create();

    expect(BudgetLineItem::query()->visibleTo($user)->count())->toBe(0);
});
