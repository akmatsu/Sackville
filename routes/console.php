<?php

use App\Jobs\SyncTdxHardwareModels;
use App\Jobs\SyncTdxMetronet;
use App\Jobs\SyncTdxMobileDevices;
use App\Jobs\SyncTdxPublicWifi;
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

$publicWifiSyncSchedule = SyncSchedule::forIntegration('tdx-public-wifi');

Schedule::job(new SyncTdxPublicWifi)
    ->cron($publicWifiSyncSchedule->toCronExpression())
    ->timezone($publicWifiSyncSchedule->timezone)
    ->name('tdx-public-wifi-sync');

$metronetSyncSchedule = SyncSchedule::forIntegration('tdx-metronet');

Schedule::job(new SyncTdxMetronet)
    ->cron($metronetSyncSchedule->toCronExpression())
    ->timezone($metronetSyncSchedule->timezone)
    ->name('tdx-metronet-sync');
