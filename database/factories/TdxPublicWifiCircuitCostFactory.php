<?php

namespace Database\Factories;

use App\Models\TdxPublicWifiCircuit;
use App\Models\TdxPublicWifiCircuitCost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TdxPublicWifiCircuitCost>
 */
class TdxPublicWifiCircuitCostFactory extends Factory
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
            'tdx_public_wifi_circuit_id' => TdxPublicWifiCircuit::factory(),
            'fiscal_year' => fake()->numberBetween(26, 30),
            'monthly_cost' => $monthlyCost,
            'yearly_cost' => round($monthlyCost * 12, 2),
            'purchase_cost' => 0,
        ];
    }
}
