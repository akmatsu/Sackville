<?php

use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\User;
use App\Models\Vendor;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists vendors', function () {
    $vendors = Vendor::factory()->count(3)->create();

    livewire(ListVendors::class)
        ->assertCanSeeTableRecords($vendors);
});

it('creates a vendor', function () {
    livewire(CreateVendor::class)
        ->fillForm([
            'name' => 'Acme Hardware Co',
            'contact_email' => 'sales@acmehardware.test',
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Vendor::class, [
        'name' => 'Acme Hardware Co',
        'contact_email' => 'sales@acmehardware.test',
    ]);
});

it('requires a name to create a vendor', function () {
    livewire(CreateVendor::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('updates a vendor', function () {
    $vendor = Vendor::factory()->create();

    livewire(EditVendor::class, ['record' => $vendor->getKey()])
        ->fillForm(['name' => 'Updated Vendor Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vendor->refresh()->name)->toBe('Updated Vendor Name');
});
