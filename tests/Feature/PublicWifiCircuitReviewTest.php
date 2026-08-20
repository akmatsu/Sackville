<?php

use App\Enums\BudgetCycleStatus;
use App\Models\BudgetCycle;
use App\Models\PublicWifiCircuitReview;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\TdxPublicWifiCircuit;
use App\Models\User;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

function setUpPublicWifiReviewFixtures(array $circuitOverrides = []): array
{
    $cycle = BudgetCycle::factory()->create([
        'fiscal_year' => 28,
        'status' => BudgetCycleStatus::Open,
    ]);

    $division = ResponsibleDivision::factory()->create([
        'department_name' => 'Information Technology',
        'name' => 'Business Operations',
    ]);

    $circuit = TdxPublicWifiCircuit::factory()->create(array_merge([
        'assigned_department_code' => 'Information Technology',
        'responsible_division_id' => $division->id,
        'status' => 'Active',
    ], $circuitOverrides));

    return compact('cycle', 'division', 'circuit');
}

test('a user with edit responsibility over the circuit scope sees it and marks it still needed with a justification', function () {
    $user = User::factory()->create();
    ['cycle' => $cycle, 'division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->assertSee($circuit->location_name)
        ->set("reviews.{$circuit->id}.still_needed", '1')
        ->set("reviews.{$circuit->id}.justification", 'Still serves the public lobby.')
        ->call('save', $circuit->id)
        ->assertHasNoErrors();

    assertDatabaseHas(PublicWifiCircuitReview::class, [
        'budget_cycle_id' => $cycle->id,
        'tdx_public_wifi_circuit_id' => $circuit->id,
        'still_needed' => true,
        'justification' => 'Still serves the public lobby.',
        'reviewed_by_id' => $user->id,
    ]);
});

test('marking a circuit still needed without a justification fails validation', function () {
    $user = User::factory()->create();
    ['division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->set("reviews.{$circuit->id}.still_needed", '1')
        ->call('save', $circuit->id)
        ->assertHasErrors(['justification']);

    assertDatabaseCount(PublicWifiCircuitReview::class, 0);
});

test('a user can mark a circuit no longer needed without a justification, and any justification is discarded', function () {
    $user = User::factory()->create();
    ['cycle' => $cycle, 'division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->set("reviews.{$circuit->id}.still_needed", '0')
        ->set("reviews.{$circuit->id}.justification", 'This should not be saved.')
        ->call('save', $circuit->id)
        ->assertHasNoErrors();

    assertDatabaseHas(PublicWifiCircuitReview::class, [
        'budget_cycle_id' => $cycle->id,
        'tdx_public_wifi_circuit_id' => $circuit->id,
        'still_needed' => false,
        'justification' => null,
    ]);
});

test('re-saving a review updates the existing row instead of duplicating it', function () {
    $user = User::factory()->create();
    ['division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::public-wifi.reviews')
        ->set("reviews.{$circuit->id}.still_needed", '1')
        ->set("reviews.{$circuit->id}.justification", 'Original justification.')
        ->call('save', $circuit->id);

    $component->set("reviews.{$circuit->id}.justification", 'Updated justification.')
        ->call('save', $circuit->id);

    assertDatabaseCount(PublicWifiCircuitReview::class, 1);
    assertDatabaseHas(PublicWifiCircuitReview::class, [
        'tdx_public_wifi_circuit_id' => $circuit->id,
        'justification' => 'Updated justification.',
    ]);
});

test('a view-role responsibility renders the circuit read-only', function () {
    $user = User::factory()->create();
    ['division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->assertSee($circuit->location_name)
        ->assertSee('View only')
        ->call('edit', $circuit->id)
        ->assertStatus(403);

    assertDatabaseCount(PublicWifiCircuitReview::class, 0);
});

test('a circuit outside the user\'s responsibility scope is not shown', function () {
    $user = User::factory()->create();
    ['circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    $otherDivision = ResponsibleDivision::factory()->create();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $otherDivision->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->assertDontSee($circuit->location_name);
});

test('a surplus circuit is not shown', function () {
    $user = User::factory()->create();
    ['division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures(['status' => 'Surplus']);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->assertDontSee($circuit->location_name);
});

test('a user with edit responsibility over the circuit location sees it', function () {
    $user = User::factory()->create();
    ['division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    $location = ResponsibleLocation::factory()->create(['responsible_division_id' => $division->id, 'name' => 'Willow']);
    $circuit->update(['responsible_location_id' => $location->id]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'location',
        'responsible_location_id' => $location->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->assertSee($circuit->location_name);
});

test('clearing a review deletes it and resets the row to pending', function () {
    $user = User::factory()->create();
    ['cycle' => $cycle, 'division' => $division, 'circuit' => $circuit] = setUpPublicWifiReviewFixtures();

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::public-wifi.reviews')
        ->set("reviews.{$circuit->id}.still_needed", '0')
        ->call('save', $circuit->id);

    $component->call('clear', $circuit->id);

    assertDatabaseCount(PublicWifiCircuitReview::class, 0);
    expect($component->get("reviews.{$circuit->id}.still_needed"))->toBe('');
});

test('the status filter narrows to circuits matching the selected review decision', function () {
    $user = User::factory()->create();
    ['division' => $division, 'circuit' => $pendingCircuit] = setUpPublicWifiReviewFixtures(['location_name' => 'Pending Circuit']);

    $keptCircuit = TdxPublicWifiCircuit::factory()->create([
        'assigned_department_code' => 'Information Technology',
        'responsible_division_id' => $division->id,
        'location_name' => 'Kept Circuit',
        'status' => 'Active',
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);

    $this->actingAs($user);

    $component = livewire('pages::public-wifi.reviews')
        ->set("reviews.{$keptCircuit->id}.still_needed", '1')
        ->set("reviews.{$keptCircuit->id}.justification", 'Needed for the public lobby.')
        ->call('save', $keptCircuit->id);

    $component->set('statusFilter', 'keeping')
        ->assertSee('Kept Circuit')
        ->assertDontSee('Pending Circuit');

    $component->set('statusFilter', 'pending')
        ->assertSee('Pending Circuit')
        ->assertDontSee('Kept Circuit');
});

test('searching narrows the table to circuits matching the search text', function () {
    $user = User::factory()->create();
    ['division' => $division, 'circuit' => $matchingCircuit] = setUpPublicWifiReviewFixtures(['location_name' => 'DSJ Library']);

    $otherCircuit = TdxPublicWifiCircuit::factory()->create([
        'assigned_department_code' => 'Information Technology',
        'responsible_division_id' => $division->id,
        'location_name' => 'Fire Station 5',
        'status' => 'Active',
    ]);

    Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'view',
    ]);

    $this->actingAs($user);

    livewire('pages::public-wifi.reviews')
        ->assertSee($matchingCircuit->location_name)
        ->assertSee($otherCircuit->location_name)
        ->set('search', 'DSJ')
        ->assertSee($matchingCircuit->location_name)
        ->assertDontSee($otherCircuit->location_name);
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

    livewire('pages::public-wifi.reviews')
        ->assertOk()
        ->assertSee('No open budget cycle');
});
