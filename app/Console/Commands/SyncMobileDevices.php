<?php

namespace App\Console\Commands;

use App\Jobs\SyncTdxMobileDevices;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tdx:sync-mobile-devices {--now : Run the sync immediately instead of queueing it}')]
#[Description('Sync mobile devices from Team Dynamix')]
class SyncMobileDevices extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $job = new SyncTdxMobileDevices;

        if ($this->option('now')) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }

        $this->info('TDX mobile device sync '.($this->option('now') ? 'completed.' : 'queued.'));

        return self::SUCCESS;
    }
}
