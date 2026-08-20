<?php

use App\Jobs\SyncTdxPublicWifi;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseCount;

it('queues the sync job by default', function () {
    Queue::fake();

    $this->artisan('tdx:sync-public-wifi')
        ->assertSuccessful();

    Queue::assertPushed(SyncTdxPublicWifi::class);
    assertDatabaseCount(SyncRun::class, 0);
});

it('runs the sync job immediately with --now', function () {
    $this->artisan('tdx:sync-public-wifi', ['--now' => true])
        ->assertSuccessful();

    assertDatabaseCount(SyncRun::class, 1);
});
