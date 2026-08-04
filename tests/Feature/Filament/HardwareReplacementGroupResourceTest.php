<?php

use App\Filament\Resources\HardwareReplacementGroups\Pages\CreateHardwareReplacementGroup;
use App\Filament\Resources\HardwareReplacementGroups\Pages\EditHardwareReplacementGroup;
use App\Filament\Resources\HardwareReplacementGroups\Pages\ListHardwareReplacementGroups;
use App\Filament\Resources\HardwareReplacementGroups\RelationManagers\EligibleModelsRelationManager;
use App\Filament\Resources\HardwareReplacementGroups\RelationManagers\ReplaceableCategoriesRelationManager;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareReplacementGroup;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists hardware replacement groups', function () {
    $groups = HardwareReplacementGroup::factory()->count(3)->create();

    livewire(ListHardwareReplacementGroups::class)
        ->assertCanSeeTableRecords($groups);
});

it('creates a hardware replacement group', function () {
    livewire(CreateHardwareReplacementGroup::class)
        ->fillForm([
            'name' => 'Standard Laptop Refresh',
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(HardwareReplacementGroup::class, ['name' => 'Standard Laptop Refresh']);
});

it('requires a unique name to create a hardware replacement group', function () {
    HardwareReplacementGroup::factory()->create(['name' => 'Standard Laptop Refresh']);

    livewire(CreateHardwareReplacementGroup::class)
        ->fillForm(['name' => 'Standard Laptop Refresh'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

it('updates a hardware replacement group', function () {
    $group = HardwareReplacementGroup::factory()->create();

    livewire(EditHardwareReplacementGroup::class, ['record' => $group->getKey()])
        ->fillForm(['name' => 'Updated Group Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($group->refresh()->name)->toBe('Updated Group Name');
});

it('attaches an eligible category to a hardware replacement group', function () {
    $group = HardwareReplacementGroup::factory()->create();
    $category = HardwareCategory::factory()->create();

    livewire(ReplaceableCategoriesRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => EditHardwareReplacementGroup::class,
    ])
        ->callAction(TestAction::make(AttachAction::class)->table(), data: ['recordId' => $category->id]);

    expect($group->replaceableCategories()->whereKey($category->id)->exists())->toBeTrue();
});

it('attaches an eligible model to a hardware replacement group', function () {
    $group = HardwareReplacementGroup::factory()->create();
    $model = HardwareModel::factory()->create();

    livewire(EligibleModelsRelationManager::class, [
        'ownerRecord' => $group,
        'pageClass' => EditHardwareReplacementGroup::class,
    ])
        ->callAction(TestAction::make(AttachAction::class)->table(), data: ['recordId' => $model->id]);

    expect($group->eligibleModels()->whereKey($model->id)->exists())->toBeTrue();
});
