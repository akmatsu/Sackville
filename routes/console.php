<?php

use App\Jobs\SyncTdxHardwareModels;
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
