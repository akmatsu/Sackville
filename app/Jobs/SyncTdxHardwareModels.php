<?php

namespace App\Jobs;

use App\Enums\SyncRunStatus;
use App\Models\SyncRun;
use App\Support\Tdx\TdxClient;
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
     * TODO: confirm with Freedom / Brooke / Katie, then reconcile the raw TDX
     * rows into tdx_assets / hardware_models. For now this only proves
     * connectivity and reports how many rows TDX returned.
     *
     * @return array{synced: int, failed: int}
     */
    protected function sync(): array
    {
        $client = new TdxClient;

        $token = $client->authenticate();
        $workstations = $client->getWorkstations($token);

        return ['synced' => count($workstations), 'failed' => 0];
    }
}
