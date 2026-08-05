<?php

use App\Enums\SyncRunStatus;
use App\Jobs\SyncTdxHardwareModels;
use App\Models\SyncRun;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('records a successful sync run', function () {
    (new SyncTdxHardwareModels)->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('has a stable unique id so duplicate runs are not queued concurrently', function () {
    expect((new SyncTdxHardwareModels)->uniqueId())->toBe('tdx-hardware-models-sync');
});
