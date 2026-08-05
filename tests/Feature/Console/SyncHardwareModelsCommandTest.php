<?php

use App\Jobs\SyncTdxHardwareModels;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseCount;

it('queues the sync job by default', function () {
    Queue::fake();

    $this->artisan('tdx:sync-hardware-models')
        ->assertSuccessful();

    Queue::assertPushed(SyncTdxHardwareModels::class);
    assertDatabaseCount(SyncRun::class, 0);
});

it('runs the sync job immediately with --now', function () {
    $this->artisan('tdx:sync-hardware-models', ['--now' => true])
        ->assertSuccessful();

    assertDatabaseCount(SyncRun::class, 1);
});
