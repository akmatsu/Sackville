<?php

namespace App\Models;

use App\Enums\SyncFrequency;
use Database\Factories\SyncScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SyncSchedule extends Model
{
    /** @use HasFactory<SyncScheduleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'integration',
        'frequency',
        'time_of_day',
        'interval_hours',
        'timezone',
    ];

    protected $casts = [
        'frequency' => SyncFrequency::class,
        'interval_hours' => 'integer',
    ];

    /**
     * The configured schedule for an integration, falling back to config
     * defaults if no row exists yet (e.g. before this table is migrated).
     */
    public static function forIntegration(string $integration): self
    {
        $schedule = Schema::hasTable('sync_schedules')
            ? static::query()->firstWhere('integration', $integration)
            : null;

        $configSection = static::configSectionFor($integration);

        return $schedule ?? new self([
            'integration' => $integration,
            'frequency' => config("tdx.{$configSection}.frequency"),
            'time_of_day' => config("tdx.{$configSection}.time_of_day"),
            'interval_hours' => config("tdx.{$configSection}.interval_hours"),
            'timezone' => config("tdx.{$configSection}.timezone"),
        ]);
    }

    /**
     * Maps an integration key to its config/tdx.php section, for the
     * pre-migration fallback above.
     */
    protected static function configSectionFor(string $integration): string
    {
        return match ($integration) {
            'tdx-mobile' => 'mobile_sync',
            default => 'hardware_sync',
        };
    }

    public function toCronExpression(): string
    {
        return match ($this->frequency) {
            SyncFrequency::EveryNHours => sprintf('0 */%d * * *', $this->interval_hours ?? 1),
            SyncFrequency::Daily => $this->dailyCronExpression(),
        };
    }

    protected function dailyCronExpression(): string
    {
        [$hour, $minute] = array_pad(explode(':', $this->time_of_day ?? '23:00'), 2, '0');

        return sprintf('%d %d * * *', (int) $minute, (int) $hour);
    }
}
