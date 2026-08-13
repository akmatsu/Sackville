<?php

use App\Jobs\SyncTdxHardwareModels;
use App\Jobs\SyncTdxMobileDevices;
use App\Models\SyncSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$hardwareSyncSchedule = SyncSchedule::forIntegration('tdx');

Schedule::job(new SyncTdxHardwareModels)
    ->cron($hardwareSyncSchedule->toCronExpression())
    ->timezone($hardwareSyncSchedule->timezone)
    ->name('tdx-hardware-models-sync');

$mobileSyncSchedule = SyncSchedule::forIntegration('tdx-mobile');

Schedule::job(new SyncTdxMobileDevices)
    ->cron($mobileSyncSchedule->toCronExpression())
    ->timezone($mobileSyncSchedule->timezone)
    ->name('tdx-mobile-devices-sync');
