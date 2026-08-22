<?php

use App\Enums\BudgetCycleStatus;
use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use App\Enums\NetworkRequestSource;
use App\Models\BudgetCycle;
use App\Models\BudgetLineItem;
use App\Models\GlCode;
use App\Models\LineItemGlAllocation;
use App\Models\ObjectCode;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\SubObjectCode;
use App\Models\TdxPublicWifiCircuit;
use App\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function setUpPublicWifiNewRequestFixtures(): array
{
    $cycle = BudgetCycle::factory()->create([
        'fiscal_year' => 28,
        'status' => BudgetCycleStatus::Open,
    ]);

    $division = ResponsibleDivision::factory()->create([
        'department_name' => 'Information Technology',
        'name' => 'Business Operations',
    ]);

    return compact('cycle', 'division');
}

function actingAsPublicWifiEditor(ResponsibleDivision $division): User
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

test('a user with edit responsibility over a division can submit a new circuit request', function () {
    ['division' => $division, 'cycle' => $cycle] = setUpPublicWifiNewRequestFixtures();
    $user = actingAsPublicWifiEditor($division);

    livewire('pages::public-wifi.reviews')
        ->call('openNewRequest')
        ->set('newRequest.responsible_division_id', $division->id)
        ->set('newRequest.location', 'Talkeetna Community Center')
        ->set('newRequest.justification', 'New public meeting space needs wifi coverage.')
        ->call('saveRequest')
        ->assertHasNoErrors();

    assertDatabaseHas(BudgetLineItem::class, [
        'budget_cycle_id' => $cycle->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::Network->value,
        'description' => 'Talkeetna Community Center',
        'justification' => 'New public meeting space needs wifi coverage.',
        'status' => BudgetLineItemStatus::NotStarted->value,
        'created_by_id' => $user->id,
    ]);
});

test('a division with zero existing circuits is still requestable when the user has direct division responsibility', function () {
    ['division' => $division] = setUpPublicWifiNewRequestFixtures();
    actingAsPublicWifiEditor($division);

    expect(TdxPublicWifiCircuit::query()->where('responsible_division_id', $division->id)->count())->toBe(0);

    livewire('pages::public-wifi.reviews')
        ->assertSee('Request a new circuit')
        ->call('openNewRequest')
        ->assertStatus(200);
});

test('a user without edit responsibility over any division cannot see or submit the request form', function () {
    setUpPublicWifiNewRequestFixtures();
    $user = User::factory()->create();
    test()->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->assertDontSee('Request a new circuit')
        ->call('openNewRequest')
        ->assertStatus(403);

    assertDatabaseCount(BudgetLineItem::class, 0);
});

test('the gl code auto-resolves from the division\'s most-used existing circuit gl code', function () {
    ['division' => $division] = setUpPublicWifiNewRequestFixtures();
    actingAsPublicWifiEditor($division);

    $objectCode = ObjectCode::factory()->create(['code' => '421']);
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => '421', 'code' => '100']);

    $referenceGlCode = GlCode::factory()->create();
    TdxPublicWifiCircuit::factory()->create([
        'responsible_division_id' => $division->id,
        'gl_code_id' => $referenceGlCode->id,
    ]);

    $targetGlCode = GlCode::factory()->create([
        'fund_code' => $referenceGlCode->fund_code,
        'department_code' => $referenceGlCode->department_code,
        'division_id' => $referenceGlCode->division_id,
        'object_code' => $objectCode->code,
        'sub_object_code_id' => $subObjectCode->id,
    ]);

    livewire('pages::public-wifi.reviews')
        ->call('openNewRequest')
        ->set('newRequest.responsible_division_id', $division->id)
        ->set('newRequest.location', 'Big Lake Community Center')
        ->set('newRequest.justification', 'New location needs coverage.')
        ->call('saveRequest');

    $item = BudgetLineItem::query()->where('item_type', BudgetLineItemType::Network)->firstOrFail();

    assertDatabaseHas(LineItemGlAllocation::class, [
        'budget_line_item_id' => $item->id,
        'gl_code_id' => $targetGlCode->id,
        'percent' => 100,
    ]);
});

test('the gl code is left pending when the division has no existing gl-coded circuit', function () {
    ['division' => $division] = setUpPublicWifiNewRequestFixtures();
    actingAsPublicWifiEditor($division);

    livewire('pages::public-wifi.reviews')
        ->call('openNewRequest')
        ->set('newRequest.responsible_division_id', $division->id)
        ->set('newRequest.location', 'Big Lake Community Center')
        ->set('newRequest.justification', 'New location needs coverage.')
        ->call('saveRequest');

    $item = BudgetLineItem::query()->where('item_type', BudgetLineItemType::Network)->firstOrFail();

    assertDatabaseCount(LineItemGlAllocation::class, 0);
    expect($item->glAllocations)->toBeEmpty();
});

test('the requester can edit their own not-started request, and the location round-trips through the form', function () {
    ['division' => $division] = setUpPublicWifiNewRequestFixtures();
    $requester = actingAsPublicWifiEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::Network,
        'network_source' => NetworkRequestSource::PublicWifi,
        'description' => 'Original Location',
        'justification' => 'Original justification.',
        'status' => BudgetLineItemStatus::NotStarted,
        'created_by_id' => $requester->id,
    ]);

    livewire('pages::public-wifi.reviews')
        ->call('editRequest', $item->id)
        ->assertSet('newRequest.location', 'Original Location')
        ->set('newRequest.location', 'Updated Location')
        ->set('newRequest.justification', 'Updated justification.')
        ->call('saveRequest')
        ->assertHasNoErrors();

    assertDatabaseHas(BudgetLineItem::class, [
        'id' => $item->id,
        'description' => 'Updated Location',
        'justification' => 'Updated justification.',
    ]);
});

test('another user within the same responsibility scope can see but not edit the request', function () {
    ['division' => $division] = setUpPublicWifiNewRequestFixtures();
    $requester = actingAsPublicWifiEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::Network,
        'network_source' => NetworkRequestSource::PublicWifi,
        'description' => 'Shared Location',
        'justification' => 'Growth coverage.',
        'status' => BudgetLineItemStatus::NotStarted,
        'created_by_id' => $requester->id,
    ]);

    actingAsPublicWifiEditor($division);

    livewire('pages::public-wifi.reviews')
        ->assertSee('Growth coverage.')
        ->assertSee('View only')
        ->call('editRequest', $item->id)
        ->assertStatus(403);
});

test('a request that is no longer not-started cannot be edited or deleted by the requester', function () {
    ['division' => $division] = setUpPublicWifiNewRequestFixtures();
    $requester = actingAsPublicWifiEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::Network,
        'network_source' => NetworkRequestSource::PublicWifi,
        'description' => 'Shared Location',
        'justification' => 'Growth coverage.',
        'status' => BudgetLineItemStatus::InProgress,
        'created_by_id' => $requester->id,
    ]);

    livewire('pages::public-wifi.reviews')
        ->call('editRequest', $item->id)
        ->assertStatus(403);

    livewire('pages::public-wifi.reviews')
        ->call('deleteRequest', $item->id)
        ->assertStatus(403);
});

test('the requester can delete their own not-started request', function () {
    ['division' => $division] = setUpPublicWifiNewRequestFixtures();
    $requester = actingAsPublicWifiEditor($division);

    $item = BudgetLineItem::factory()->create([
        'budget_cycle_id' => BudgetCycle::query()->open()->firstOrFail()->id,
        'responsible_division_id' => $division->id,
        'item_type' => BudgetLineItemType::Network,
        'network_source' => NetworkRequestSource::PublicWifi,
        'description' => 'Shared Location',
        'justification' => 'Growth coverage.',
        'status' => BudgetLineItemStatus::NotStarted,
        'created_by_id' => $requester->id,
    ]);

    livewire('pages::public-wifi.reviews')
        ->call('deleteRequest', $item->id)
        ->assertHasNoErrors();

    assertDatabaseCount(BudgetLineItem::class, 0);
});
