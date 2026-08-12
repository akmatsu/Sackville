<?php

use App\Filament\Resources\ResponsibleDivisions\Pages\CreateResponsibleDivision;
use App\Filament\Resources\ResponsibleDivisions\Pages\EditResponsibleDivision;
use App\Filament\Resources\ResponsibleDivisions\Pages\ListResponsibleDivisions;
use App\Filament\Resources\ResponsibleDivisions\RelationManagers\LocationsRelationManager;
use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists responsible divisions', function () {
    $divisions = ResponsibleDivision::factory()->count(3)->create();

    livewire(ListResponsibleDivisions::class)
        ->assertCanSeeTableRecords($divisions);
});

it('creates a responsible division', function () {
    livewire(CreateResponsibleDivision::class)
        ->fillForm([
            'department_name' => 'Information Technology',
            'name' => 'Business Operations',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(ResponsibleDivision::class, [
        'department_name' => 'Information Technology',
        'name' => 'Business Operations',
    ]);
});

it('updates a responsible division', function () {
    $division = ResponsibleDivision::factory()->create();

    livewire(EditResponsibleDivision::class, ['record' => $division->getKey()])
        ->fillForm(['name' => 'Renamed Division'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($division->refresh()->name)->toBe('Renamed Division');
});

it('lists a responsible division\'s locations', function () {
    $division = ResponsibleDivision::factory()->create();
    $locations = ResponsibleLocation::factory()->count(2)->create(['responsible_division_id' => $division->id]);

    livewire(LocationsRelationManager::class, [
        'ownerRecord' => $division,
        'pageClass' => EditResponsibleDivision::class,
    ])
        ->assertCanSeeTableRecords($locations);
});

it('creates a location under a responsible division', function () {
    $division = ResponsibleDivision::factory()->create();

    livewire(LocationsRelationManager::class, [
        'ownerRecord' => $division,
        'pageClass' => EditResponsibleDivision::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: ['name' => 'Talkeetna'])
        ->assertHasNoActionErrors();

    assertDatabaseHas(ResponsibleLocation::class, [
        'responsible_division_id' => $division->id,
        'name' => 'Talkeetna',
    ]);
});
