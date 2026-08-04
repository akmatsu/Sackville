<?php

namespace Database\Factories;

use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncRun>
 */
class SyncRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'integration' => 'tdx',
            'started_at' => $startedAt,
            'finished_at' => (clone $startedAt)->modify('+'.fake()->numberBetween(10, 600).' seconds'),
            'records_synced' => fake()->numberBetween(0, 500),
            'records_failed' => 0,
            'status' => 'success',
            'errors' => null,
        ];
    }
}
