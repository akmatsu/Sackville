<?php

namespace App\Console\Commands;

use App\Jobs\SyncTdxMetronet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tdx:sync-metronet {--now : Run the sync immediately instead of queueing it}')]
#[Description('Sync Metronet circuits from Team Dynamix')]
class SyncMetronet extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $job = new SyncTdxMetronet;

        if ($this->option('now')) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }

        $this->info('TDX Metronet sync '.($this->option('now') ? 'completed.' : 'queued.'));

        return self::SUCCESS;
    }
}
