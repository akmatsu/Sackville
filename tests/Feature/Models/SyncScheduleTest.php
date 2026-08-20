<?php

use App\Enums\SyncFrequency;
use App\Models\SyncSchedule;
use Illuminate\Support\Facades\Schema;

it('seeds a default daily tdx schedule via migration', function () {
    $schedule = SyncSchedule::query()->firstWhere('integration', 'tdx');

    expect($schedule)->not->toBeNull();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe('23:00');
    expect($schedule->timezone)->toBe('America/Anchorage');
});

it('seeds a default daily tdx-mobile schedule via migration', function () {
    $schedule = SyncSchedule::query()->firstWhere('integration', 'tdx-mobile');

    expect($schedule)->not->toBeNull();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe('23:30');
    expect($schedule->timezone)->toBe('America/Anchorage');
});

it('seeds a default daily tdx-public-wifi schedule via migration', function () {
    $schedule = SyncSchedule::query()->firstWhere('integration', 'tdx-public-wifi');

    expect($schedule)->not->toBeNull();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe('00:00');
    expect($schedule->timezone)->toBe('America/Anchorage');
});

it('seeds a default daily tdx-metronet schedule via migration', function () {
    $schedule = SyncSchedule::query()->firstWhere('integration', 'tdx-metronet');

    expect($schedule)->not->toBeNull();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe('00:30');
    expect($schedule->timezone)->toBe('America/Anchorage');
});

it('builds a cron expression for a daily schedule', function () {
    $schedule = SyncSchedule::factory()->make([
        'frequency' => SyncFrequency::Daily,
        'time_of_day' => '23:15',
    ]);

    expect($schedule->toCronExpression())->toBe('15 23 * * *');
});

it('builds a cron expression for an every-n-hours schedule', function () {
    $schedule = SyncSchedule::factory()->make([
        'frequency' => SyncFrequency::EveryNHours,
        'interval_hours' => 4,
    ]);

    expect($schedule->toCronExpression())->toBe('0 */4 * * *');
});

it('falls back to config defaults for an integration with no configured schedule', function () {
    $schedule = SyncSchedule::forIntegration('unconfigured-integration');

    expect($schedule->exists)->toBeFalse();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe(config('tdx.hardware_sync.time_of_day'));
});

it('falls back to the mobile_sync config defaults when no tdx-mobile schedule row exists', function () {
    SyncSchedule::query()->where('integration', 'tdx-mobile')->delete();

    $schedule = SyncSchedule::forIntegration('tdx-mobile');

    expect($schedule->exists)->toBeFalse();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe(config('tdx.mobile_sync.time_of_day'));
});

it('falls back to the public_wifi_sync config defaults when no tdx-public-wifi schedule row exists', function () {
    SyncSchedule::query()->where('integration', 'tdx-public-wifi')->delete();

    $schedule = SyncSchedule::forIntegration('tdx-public-wifi');

    expect($schedule->exists)->toBeFalse();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe(config('tdx.public_wifi_sync.time_of_day'));
});

it('falls back to the metronet_sync config defaults when no tdx-metronet schedule row exists', function () {
    SyncSchedule::query()->where('integration', 'tdx-metronet')->delete();

    $schedule = SyncSchedule::forIntegration('tdx-metronet');

    expect($schedule->exists)->toBeFalse();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe(config('tdx.metronet_sync.time_of_day'));
});

it('falls back to config defaults instead of throwing when the database is unreachable', function () {
    // Simulates routes/console.php being loaded before the database exists,
    // e.g. during `composer install`'s package:discover step in CI.
    Schema::shouldReceive('hasTable')
        ->with('sync_schedules')
        ->andThrow(new PDOException('simulated: database unreachable'));

    $schedule = SyncSchedule::forIntegration('tdx');

    expect($schedule->exists)->toBeFalse();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe(config('tdx.hardware_sync.time_of_day'));
});
