<?php

use App\Enums\SyncFrequency;
use App\Models\SyncSchedule;

it('seeds a default daily tdx schedule via migration', function () {
    $schedule = SyncSchedule::query()->firstWhere('integration', 'tdx');

    expect($schedule)->not->toBeNull();
    expect($schedule->frequency)->toBe(SyncFrequency::Daily);
    expect($schedule->time_of_day)->toBe('23:00');
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
