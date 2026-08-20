<?php

use App\Filament\Resources\HardwareCategories\Pages\CreateHardwareCategory;
use App\Filament\Resources\HardwareCategories\Pages\EditHardwareCategory;
use App\Filament\Resources\HardwareCategories\Pages\ListHardwareCategories;
use App\Models\HardwareCategory;
use App\Models\ObjectCode;
use App\Models\SubObjectCode;
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

it('sets the default gl object and sub-object codes used for new-asset requests', function () {
    $category = HardwareCategory::factory()->create();
    $objectCode = ObjectCode::factory()->create();
    $subObjectCode = SubObjectCode::factory()->create(['object_code' => $objectCode->code]);

    livewire(EditHardwareCategory::class, ['record' => $category->getKey()])
        ->fillForm([
            'default_object_code' => $objectCode->code,
            'default_sub_object_code_id' => $subObjectCode->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh())
        ->default_object_code->toBe($objectCode->code)
        ->default_sub_object_code_id->toBe($subObjectCode->id);
});
