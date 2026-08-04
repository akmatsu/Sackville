<?php

use App\Filament\Resources\HardwareModels\Pages\CreateHardwareModel;
use App\Filament\Resources\HardwareModels\Pages\EditHardwareModel;
use App\Filament\Resources\HardwareModels\Pages\ListHardwareModels;
use App\Filament\Resources\HardwareModels\RelationManagers\CostsRelationManager;
use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists hardware models', function () {
    $models = HardwareModel::factory()->count(3)->create();

    livewire(ListHardwareModels::class)
        ->assertCanSeeTableRecords($models);
});

it('creates a hardware model', function () {
    $vendor = Vendor::factory()->create();
    $category = HardwareCategory::factory()->create();

    livewire(CreateHardwareModel::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'hardware_category_id' => $category->id,
            'name' => 'Latitude 5440',
            'has_docking_option' => true,
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(HardwareModel::class, [
        'vendor_id' => $vendor->id,
        'hardware_category_id' => $category->id,
        'name' => 'Latitude 5440',
        'has_docking_option' => true,
    ]);
});

it('requires a vendor, category, and name to create a hardware model', function () {
    livewire(CreateHardwareModel::class)
        ->fillForm(['vendor_id' => null, 'hardware_category_id' => null, 'name' => ''])
        ->call('create')
        ->assertHasFormErrors(['vendor_id' => 'required', 'hardware_category_id' => 'required', 'name' => 'required']);
});

it('updates a hardware model', function () {
    $model = HardwareModel::factory()->create();

    livewire(EditHardwareModel::class, ['record' => $model->getKey()])
        ->fillForm(['name' => 'Updated Model Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($model->refresh()->name)->toBe('Updated Model Name');
});

it('manages fiscal-year costs for a hardware model', function () {
    $model = HardwareModel::factory()->create();

    livewire(CostsRelationManager::class, [
        'ownerRecord' => $model,
        'pageClass' => EditHardwareModel::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'fiscal_year' => 28,
            'unit_cost' => 1200,
            'with_docking' => false,
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas('hardware_model_costs', [
        'hardware_model_id' => $model->id,
        'fiscal_year' => 28,
        'unit_cost' => 1200,
    ]);
});
