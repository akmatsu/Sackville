<?php

use App\Jobs\SyncTdxMetronet;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseCount;

it('queues the sync job by default', function () {
    Queue::fake();

    $this->artisan('tdx:sync-metronet')
        ->assertSuccessful();

    Queue::assertPushed(SyncTdxMetronet::class);
    assertDatabaseCount(SyncRun::class, 0);
});

it('runs the sync job immediately with --now', function () {
    $this->artisan('tdx:sync-metronet', ['--now' => true])
        ->assertSuccessful();

    assertDatabaseCount(SyncRun::class, 1);
});
