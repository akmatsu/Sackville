<?php

use App\Enums\SyncRunStatus;
use App\Jobs\SyncTdxPublicWifi;
use App\Models\SyncRun;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('creates a successful sync run with zero records, since the TDX integration is not wired up yet', function () {
    (new SyncTdxPublicWifi)->handle();

    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx-public-wifi',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('has a stable unique id so duplicate runs are not queued concurrently', function () {
    expect((new SyncTdxPublicWifi)->uniqueId())->toBe('tdx-public-wifi-sync');
});

it('updates a pre-created running sync run instead of creating a new one', function () {
    $syncRun = SyncRun::factory()->running()->create(['integration' => 'tdx-public-wifi']);

    (new SyncTdxPublicWifi($syncRun->id))->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'id' => $syncRun->id,
        'integration' => 'tdx-public-wifi',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});
