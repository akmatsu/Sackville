<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\RelationManagers\ResponsibilitiesRelationManager;
use App\Models\Responsibility;
use App\Models\User;
use Filament\Actions\CreateAction;
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

it('creates a responsibility for a user', function () {
    $user = User::factory()->create();

    livewire(ResponsibilitiesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'scope_type' => 'division',
            'scope_value' => '117',
            'role' => 'edit',
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas(Responsibility::class, [
        'user_id' => $user->id,
        'scope_type' => 'division',
        'scope_value' => '117',
        'role' => 'edit',
    ]);
});
