<?php

namespace App\Jobs;

use App\Enums\SyncRunStatus;
use App\Models\SyncRun;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTdxPublicWifi implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Keep duplicate runs off the queue while one is already processing or
     * scheduled — the scheduler and manual "sync now" trigger can otherwise
     * both dispatch within the same window.
     */
    public int $uniqueFor = 3600;

    public function __construct(public readonly ?int $syncRunId = null)
    {
        //
    }

    public function uniqueId(): string
    {
        return 'tdx-public-wifi-sync';
    }

    public function handle(): void
    {
        $syncRun = $this->syncRunId !== null
            ? SyncRun::findOrFail($this->syncRunId)
            : SyncRun::create([
                'integration' => 'tdx-public-wifi',
                'started_at' => now(),
                'status' => SyncRunStatus::Running,
            ]);

        try {
            $result = $this->sync();

            $syncRun->update([
                'finished_at' => now(),
                'records_synced' => $result['synced'],
                'records_failed' => $result['failed'],
                'status' => SyncRunStatus::Success,
                'errors' => null,
            ]);
        } catch (Throwable $e) {
            $syncRun->update([
                'finished_at' => now(),
                'records_synced' => 0,
                'records_failed' => 0,
                'status' => SyncRunStatus::Failed,
                'errors' => ['message' => $e->getMessage()],
            ]);

            Log::error('TDX public wifi sync failed.', ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * TODO: confirm the report ID/URL with Freedom, then replace this with
     * the actual TDX API pull once available. This placeholder does no work
     * so the scheduling, manual-trigger, and sync_runs logging pipeline can
     * be exercised before the HTTP integration is built.
     *
     * @return array{synced: int, failed: int}
     */
    protected function sync(): array
    {
        return ['synced' => 0, 'failed' => 0];
    }
}
