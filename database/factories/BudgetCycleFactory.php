<?php

namespace Database\Factories;

use App\Models\BudgetCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetCycle>
 */
class BudgetCycleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fiscal_year' => fake()->unique()->numberBetween(26, 40),
            'opens_at' => '2026-07-01',
            'closes_at' => '2026-09-30',
            'status' => 'draft',
        ];
    }
}
