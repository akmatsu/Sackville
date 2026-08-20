<?php

namespace App\Console\Commands;

use App\Jobs\SyncTdxPublicWifi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tdx:sync-public-wifi {--now : Run the sync immediately instead of queueing it}')]
#[Description('Sync public wifi circuits from Team Dynamix')]
class SyncPublicWifi extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $job = new SyncTdxPublicWifi;

        if ($this->option('now')) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }

        $this->info('TDX public wifi sync '.($this->option('now') ? 'completed.' : 'queued.'));

        return self::SUCCESS;
    }
}
