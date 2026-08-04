<?php

use App\Filament\Resources\HardwareCategories\Pages\CreateHardwareCategory;
use App\Filament\Resources\HardwareCategories\Pages\EditHardwareCategory;
use App\Filament\Resources\HardwareCategories\Pages\ListHardwareCategories;
use App\Models\HardwareCategory;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists hardware categories', function () {
    $categories = HardwareCategory::factory()->count(3)->create();

    livewire(ListHardwareCategories::class)
        ->assertCanSeeTableRecords($categories);
});

it('creates a hardware category', function () {
    livewire(CreateHardwareCategory::class)
        ->fillForm(['name' => 'Laptops'])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(HardwareCategory::class, ['name' => 'Laptops']);
});

it('requires a unique name to create a hardware category', function () {
    HardwareCategory::factory()->create(['name' => 'Laptops']);

    livewire(CreateHardwareCategory::class)
        ->fillForm(['name' => 'Laptops'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

it('updates a hardware category', function () {
    $category = HardwareCategory::factory()->create();

    livewire(EditHardwareCategory::class, ['record' => $category->getKey()])
        ->fillForm(['name' => 'Desktops'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe('Desktops');
});
