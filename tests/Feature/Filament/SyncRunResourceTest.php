<?php

use App\Enums\SyncFrequency;
use App\Enums\SyncRunStatus;
use App\Filament\Resources\SyncRuns\Pages\ListSyncRuns;
use App\Filament\Resources\SyncRuns\Pages\ViewSyncRun;
use App\Jobs\SyncTdxHardwareModels;
use App\Jobs\SyncTdxMobileDevices;
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

it('immediately shows a running sync run when a hardware model sync is triggered, before the queue picks it up', function () {
    Queue::fake();

    $component = livewire(ListSyncRuns::class)
        ->callAction('syncHardwareModels');

    $syncRun = SyncRun::query()->where('integration', 'tdx')->sole();

    expect($syncRun->status)->toBe(SyncRunStatus::Running);
    expect($syncRun->finished_at)->toBeNull();

    $component->assertCanSeeTableRecords([$syncRun]);

    Queue::assertPushed(SyncTdxHardwareModels::class, fn (SyncTdxHardwareModels $job): bool => $job->syncRunId === $syncRun->id);
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

it('allows manually triggering a mobile device sync', function () {
    Queue::fake();

    livewire(ListSyncRuns::class)
        ->callAction('syncMobileDevices')
        ->assertNotified();

    Queue::assertPushed(SyncTdxMobileDevices::class);
});

it('immediately shows a running sync run when a mobile device sync is triggered, before the queue picks it up', function () {
    Queue::fake();

    $component = livewire(ListSyncRuns::class)
        ->callAction('syncMobileDevices');

    $syncRun = SyncRun::query()->where('integration', 'tdx-mobile')->sole();

    expect($syncRun->status)->toBe(SyncRunStatus::Running);
    expect($syncRun->finished_at)->toBeNull();

    $component->assertCanSeeTableRecords([$syncRun]);

    Queue::assertPushed(SyncTdxMobileDevices::class, fn (SyncTdxMobileDevices $job): bool => $job->syncRunId === $syncRun->id);
});

it('shows the current mobile schedule when opening the configure mobile schedule action', function () {
    livewire(ListSyncRuns::class)
        ->mountAction('configureMobileSchedule')
        ->assertSchemaStateSet([
            'frequency' => SyncFrequency::Daily,
            'time_of_day' => '23:30',
        ]);
});

it('allows configuring the mobile sync schedule to run more frequently', function () {
    livewire(ListSyncRuns::class)
        ->callAction('configureMobileSchedule', data: [
            'frequency' => 'every_n_hours',
            'interval_hours' => 6,
        ])
        ->assertNotified();

    assertDatabaseHas(SyncSchedule::class, [
        'integration' => 'tdx-mobile',
        'frequency' => 'every_n_hours',
        'interval_hours' => 6,
        'time_of_day' => null,
    ]);
});
