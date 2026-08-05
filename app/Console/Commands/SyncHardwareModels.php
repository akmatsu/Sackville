<?php

namespace App\Console\Commands;

use App\Jobs\SyncTdxHardwareModels;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tdx:sync-hardware-models {--now : Run the sync immediately instead of queueing it}')]
#[Description('Sync hardware models from Team Dynamix')]
class SyncHardwareModels extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $job = new SyncTdxHardwareModels;

        if ($this->option('now')) {
            dispatch_sync($job);
        } else {
            dispatch($job);
        }

        $this->info('TDX hardware model sync '.($this->option('now') ? 'completed.' : 'queued.'));

        return self::SUCCESS;
    }
}
