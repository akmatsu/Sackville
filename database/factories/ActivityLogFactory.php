<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'table_name' => 'budget_line_items',
            'record_id' => fake()->numberBetween(1, 1000),
            'action' => 'update',
            'diff' => ['status' => ['from' => 'not_started', 'to' => 'in_progress']],
            'actor_id' => User::factory(),
            'at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
