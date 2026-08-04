<?php

namespace Database\Factories;

use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardwareModelCost>
 */
class HardwareModelCostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hardware_model_id' => HardwareModel::factory(),
            'fiscal_year' => fake()->numberBetween(26, 30),
            'unit_cost' => fake()->randomFloat(2, 200, 3000),
            'with_docking' => false,
            'docking_upcharge' => null,
        ];
    }
}
