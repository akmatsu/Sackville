<?php

namespace Database\Factories;

use App\Models\TdxMetronetCircuit;
use App\Models\TdxMetronetCircuitCost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TdxMetronetCircuitCost>
 */
class TdxMetronetCircuitCostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $monthlyCost = fake()->randomFloat(2, 20, 500);

        return [
            'tdx_metronet_circuit_id' => TdxMetronetCircuit::factory(),
            'fiscal_year' => fake()->numberBetween(26, 30),
            'monthly_cost' => $monthlyCost,
            'yearly_cost' => round($monthlyCost * 12, 2),
            'purchase_cost' => 0,
        ];
    }
}
