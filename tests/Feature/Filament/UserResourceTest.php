<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists users', function () {
    $users = User::factory()->count(3)->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('creates a user with a hashed password', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Jordan Baker',
            'email' => 'jordan.baker@example.com',
            'password' => 'a-very-secure-password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'jordan.baker@example.com')->firstOrFail();

    expect(Hash::check('a-very-secure-password', $user->password))->toBeTrue();
});

it('requires name, email, and password to create a user', function () {
    livewire(CreateUser::class)
        ->fillForm(['name' => '', 'email' => '', 'password' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'email' => 'required', 'password' => 'required']);
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Someone Else',
            'email' => 'taken@example.com',
            'password' => 'a-very-secure-password',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

it('updates a user without changing the password when left blank', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'name' => 'Updated Name',
            'password' => '',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'id' => $user->id,
        'name' => 'Updated Name',
        'password' => $originalPassword,
    ]);
});
