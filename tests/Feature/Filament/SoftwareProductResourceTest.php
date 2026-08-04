<?php

use App\Filament\Resources\SoftwareProducts\Pages\CreateSoftwareProduct;
use App\Filament\Resources\SoftwareProducts\Pages\EditSoftwareProduct;
use App\Filament\Resources\SoftwareProducts\Pages\ListSoftwareProducts;
use App\Filament\Resources\SoftwareProducts\RelationManagers\LicensesRelationManager;
use App\Models\SoftwareProduct;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists software products', function () {
    $products = SoftwareProduct::factory()->count(3)->create();

    livewire(ListSoftwareProducts::class)
        ->assertCanSeeTableRecords($products);
});

it('creates a software product', function () {
    $vendor = Vendor::factory()->create();

    livewire(CreateSoftwareProduct::class)
        ->fillForm([
            'vendor_id' => $vendor->id,
            'name' => 'Adobe Acrobat',
            'billing_frequency' => 'annual',
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(SoftwareProduct::class, [
        'vendor_id' => $vendor->id,
        'name' => 'Adobe Acrobat',
    ]);
});

it('requires a vendor and name to create a software product', function () {
    livewire(CreateSoftwareProduct::class)
        ->fillForm(['vendor_id' => null, 'name' => ''])
        ->call('create')
        ->assertHasFormErrors(['vendor_id' => 'required', 'name' => 'required']);
});

it('updates a software product', function () {
    $product = SoftwareProduct::factory()->create();

    livewire(EditSoftwareProduct::class, ['record' => $product->getKey()])
        ->fillForm(['name' => 'Updated Product Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh()->name)->toBe('Updated Product Name');
});

it('manages fiscal-year licenses for a software product', function () {
    $product = SoftwareProduct::factory()->create();

    livewire(LicensesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditSoftwareProduct::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), data: [
            'fiscal_year' => 28,
            'license_count' => 25,
            'unit_cost' => 40,
            'total_cost' => 1000,
        ])
        ->assertHasNoActionErrors();

    assertDatabaseHas('software_licenses', [
        'software_product_id' => $product->id,
        'fiscal_year' => 28,
        'license_count' => 25,
    ]);
});
