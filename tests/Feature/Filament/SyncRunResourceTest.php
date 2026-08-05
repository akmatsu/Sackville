<?php

use App\Enums\SyncFrequency;
use App\Filament\Resources\SyncRuns\Pages\ListSyncRuns;
use App\Filament\Resources\SyncRuns\Pages\ViewSyncRun;
use App\Jobs\SyncTdxHardwareModels;
use App\Models\SyncRun;
use App\Models\SyncSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;
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

it('allows manually triggering a hardware model sync', function () {
    Queue::fake();

    livewire(ListSyncRuns::class)
        ->callAction('syncHardwareModels')
        ->assertNotified();

    Queue::assertPushed(SyncTdxHardwareModels::class);
});

it('shows the current schedule when opening the configure schedule action', function () {
    livewire(ListSyncRuns::class)
        ->mountAction('configureSchedule')
        ->assertSchemaStateSet([
            'frequency' => SyncFrequency::Daily,
            'time_of_day' => '23:00',
        ]);
});

it('allows configuring the sync schedule to run more frequently', function () {
    livewire(ListSyncRuns::class)
        ->callAction('configureSchedule', data: [
            'frequency' => 'every_n_hours',
            'interval_hours' => 4,
        ])
        ->assertNotified();

    assertDatabaseHas(SyncSchedule::class, [
        'integration' => 'tdx',
        'frequency' => 'every_n_hours',
        'interval_hours' => 4,
        'time_of_day' => null,
    ]);
});
