<?php

use App\Jobs\SyncTdxMobileDevices;
use App\Models\SyncRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseCount;

it('queues the sync job by default', function () {
    Queue::fake();

    $this->artisan('tdx:sync-mobile-devices')
        ->assertSuccessful();

    Queue::assertPushed(SyncTdxMobileDevices::class);
    assertDatabaseCount(SyncRun::class, 0);
});

it('runs the sync job immediately with --now', function () {
    Http::fake([
        '*/auth' => Http::response('fake-jwt-token', 200),
        '*/reports/363*' => Http::response([], 200),
    ]);

    $this->artisan('tdx:sync-mobile-devices', ['--now' => true])
        ->assertSuccessful();

    assertDatabaseCount(SyncRun::class, 1);
});
