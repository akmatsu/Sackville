<?php

namespace Database\Factories;

use App\Enums\SyncFrequency;
use App\Models\SyncSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncSchedule>
 */
class SyncScheduleFactory extends Factory
{
    protected $model = SyncSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'integration' => 'tdx',
            'frequency' => SyncFrequency::Daily,
            'time_of_day' => '23:00',
            'interval_hours' => null,
            'timezone' => 'America/Anchorage',
        ];
    }
}
