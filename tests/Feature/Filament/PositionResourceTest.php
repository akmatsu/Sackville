<?php

use App\Filament\Resources\Positions\Pages\CreatePosition;
use App\Filament\Resources\Positions\Pages\EditPosition;
use App\Filament\Resources\Positions\Pages\ListPositions;
use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists positions', function () {
    $positions = Position::factory()->count(3)->create();

    livewire(ListPositions::class)
        ->assertCanSeeTableRecords($positions);
});

it('creates a position', function () {
    $department = Department::factory()->create();
    $division = Division::factory()->create(['department_code' => $department->code]);

    livewire(CreatePosition::class)
        ->fillForm([
            'title' => 'Network Administrator',
            'department_code' => $department->code,
            'division_id' => $division->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Position::class, [
        'title' => 'Network Administrator',
        'department_code' => $department->code,
        'division_id' => $division->id,
    ]);
});

it('requires a title, department, and division to create a position', function () {
    livewire(CreatePosition::class)
        ->fillForm(['title' => '', 'department_code' => null, 'division_id' => null])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required', 'department_code' => 'required', 'division_id' => 'required']);
});

it('updates a position', function () {
    $position = Position::factory()->create();

    livewire(EditPosition::class, ['record' => $position->getKey()])
        ->fillForm(['title' => 'Senior Network Administrator'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($position->refresh()->title)->toBe('Senior Network Administrator');
});
