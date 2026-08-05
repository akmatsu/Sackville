<?php

namespace App\Jobs;

use App\Enums\SyncRunStatus;
use App\Models\SyncRun;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTdxHardwareModels implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Keep duplicate runs off the queue while one is already processing or
     * scheduled — the scheduler and manual "sync now" trigger can otherwise
     * both dispatch within the same window.
     */
    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'tdx-hardware-models-sync';
    }

    public function handle(): void
    {
        $startedAt = now();

        try {
            $result = $this->sync();

            SyncRun::create([
                'integration' => 'tdx',
                'started_at' => $startedAt,
                'finished_at' => now(),
                'records_synced' => $result['synced'],
                'records_failed' => $result['failed'],
                'status' => SyncRunStatus::Success,
                'errors' => null,
            ]);
        } catch (Throwable $e) {
            SyncRun::create([
                'integration' => 'tdx',
                'started_at' => $startedAt,
                'finished_at' => now(),
                'records_synced' => 0,
                'records_failed' => 0,
                'status' => SyncRunStatus::Failed,
                'errors' => ['message' => $e->getMessage()],
            ]);

            Log::error('TDX hardware model sync failed.', ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * TODO: confirm with Freedom / Brooke / Katie, then replace this with the
     * actual TDX API pull and hardware_models reconciliation. This placeholder
     * does no work so the scheduling, manual-trigger, and sync_runs logging
     * pipeline can be exercised before the HTTP integration is built.
     *
     * @return array{synced: int, failed: int}
     */
    protected function sync(): array
    {
        return ['synced' => 0, 'failed' => 0];
    }
}
