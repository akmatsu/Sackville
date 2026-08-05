<?php

use App\Enums\SyncRunStatus;
use App\Jobs\SyncTdxHardwareModels;
use App\Models\SyncRun;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('records a successful sync run with the count of rows TDX returned', function () {
    Http::fake([
        '*/auth' => Http::response('fake-jwt-token', 200),
        '*/reports/362*' => Http::response([
            'DataRows' => [
                ['AssetID' => 1, 'Name' => 'IT34350'],
                ['AssetID' => 2, 'Name' => 'IT34351'],
                ['AssetID' => 3, 'Name' => 'IT34352'],
            ],
        ], 200),
    ]);

    (new SyncTdxHardwareModels)->handle();

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx',
        'status' => SyncRunStatus::Success->value,
        'records_synced' => 3,
        'records_failed' => 0,
    ]);
});

it('records a failed sync run when TDX authentication fails', function () {
    Http::fake([
        '*/auth' => Http::response('Unauthorized', 401),
    ]);

    expect(fn () => (new SyncTdxHardwareModels)->handle())
        ->toThrow(RequestException::class);

    assertDatabaseCount(SyncRun::class, 1);
    assertDatabaseHas(SyncRun::class, [
        'integration' => 'tdx',
        'status' => SyncRunStatus::Failed->value,
        'records_synced' => 0,
        'records_failed' => 0,
    ]);
});

it('has a stable unique id so duplicate runs are not queued concurrently', function () {
    expect((new SyncTdxHardwareModels)->uniqueId())->toBe('tdx-hardware-models-sync');
});
