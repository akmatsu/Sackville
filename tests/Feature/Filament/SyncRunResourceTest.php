<?php

use App\Filament\Resources\SyncRuns\Pages\ListSyncRuns;
use App\Filament\Resources\SyncRuns\Pages\ViewSyncRun;
use App\Models\SyncRun;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists sync runs', function () {
    $runs = SyncRun::factory()->count(3)->create();

    livewire(ListSyncRuns::class)
        ->assertCanSeeTableRecords($runs);
});

it('views a sync run', function () {
    $run = SyncRun::factory()->create();

    livewire(ViewSyncRun::class, ['record' => $run->getKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'integration' => $run->integration,
            'records_synced' => $run->records_synced,
        ]);
});

it('does not register create or edit routes for sync runs', function () {
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.sync-runs.create'))->toBeFalse();
    expect(app('router')->getRoutes()->hasNamedRoute('filament.admin.resources.sync-runs.edit'))->toBeFalse();
});
