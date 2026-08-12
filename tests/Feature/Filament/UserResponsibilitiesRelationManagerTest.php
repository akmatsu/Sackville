<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\RelationManagers\ResponsibilitiesRelationManager;
use App\Models\Responsibility;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists a user\'s responsibilities', function () {
    $user = User::factory()->create();
    $responsibilities = Responsibility::factory()->count(2)->create(['user_id' => $user->id]);
    Responsibility::factory()->create();

    livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertCanSeeTableRecords($responsibilities);
});

it('creates a department-scoped responsibility for a user using the scope value field', function () {
    $user = User::factory()->create();

    livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'scope_type' => 'department',
            'scope_value' => '115',
            'role' => 'edit',
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas(Responsibility::class, [
        'user_id' => $user->id,
        'scope_type' => 'department',
        'scope_value' => '115',
        'role' => 'edit',
    ]);
});

it('creates a department-scoped responsibility for a user by picking an existing department', function () {
    $user = User::factory()->create();
    ResponsibleDivision::factory()->create(['department_name' => 'Information Technology']);

    livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'scope_type' => 'department',
            'department_scope_value' => 'Information Technology',
            'role' => 'edit',
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas(Responsibility::class, [
        'user_id' => $user->id,
        'scope_type' => 'department',
        'scope_value' => 'Information Technology',
        'role' => 'edit',
    ]);
});

it('shows the previously selected department when editing a department-scoped responsibility', function () {
    $user = User::factory()->create();
    ResponsibleDivision::factory()->create(['department_name' => 'Information Technology']);
    $responsibility = Responsibility::factory()->create([
        'user_id' => $user->id,
        'scope_type' => 'department',
        'scope_value' => 'Information Technology',
    ]);

    livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->mountAction(TestAction::make(EditAction::class)->table($responsibility))
        ->assertSchemaStateSet([
            'department_scope_value' => 'Information Technology',
        ]);
});

it('reveals the matching field as the scope type is changed, live, in the create form', function () {
    $user = User::factory()->create();

    $component = livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->mountAction(TestAction::make(CreateAction::class)->table());

    $component->assertFormFieldHidden('scope_value')
        ->assertFormFieldHidden('department_scope_value')
        ->assertFormFieldHidden('responsible_division_id')
        ->assertFormFieldHidden('responsible_location_id');

    $component->set('mountedActions.0.data.scope_type', 'fund')
        ->assertFormFieldVisible('scope_value')
        ->assertFormFieldHidden('department_scope_value')
        ->assertFormFieldHidden('responsible_division_id')
        ->assertFormFieldHidden('responsible_location_id');

    $component->set('mountedActions.0.data.scope_type', 'department')
        ->assertFormFieldHidden('scope_value')
        ->assertFormFieldVisible('department_scope_value')
        ->assertFormFieldHidden('responsible_division_id')
        ->assertFormFieldHidden('responsible_location_id');

    $component->set('mountedActions.0.data.scope_type', 'division')
        ->assertFormFieldHidden('scope_value')
        ->assertFormFieldHidden('department_scope_value')
        ->assertFormFieldVisible('responsible_division_id')
        ->assertFormFieldHidden('responsible_location_id');

    $component->set('mountedActions.0.data.scope_type', 'location')
        ->assertFormFieldHidden('scope_value')
        ->assertFormFieldHidden('department_scope_value')
        ->assertFormFieldHidden('responsible_division_id')
        ->assertFormFieldVisible('responsible_location_id');
});

it('creates a division-scoped responsibility for a user by picking a responsible division', function () {
    $user = User::factory()->create();
    $division = ResponsibleDivision::factory()->create();

    livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'scope_type' => 'division',
            'responsible_division_id' => $division->id,
            'role' => 'edit',
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas(Responsibility::class, [
        'user_id' => $user->id,
        'scope_type' => 'division',
        'responsible_division_id' => $division->id,
        'role' => 'edit',
    ]);
});

it('creates a location-scoped responsibility for a user by picking a responsible location', function () {
    $user = User::factory()->create();
    $location = ResponsibleLocation::factory()->create();

    livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'scope_type' => 'location',
            'responsible_location_id' => $location->id,
            'role' => 'edit',
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas(Responsibility::class, [
        'user_id' => $user->id,
        'scope_type' => 'location',
        'responsible_location_id' => $location->id,
        'role' => 'edit',
    ]);
});
