<?php

namespace Database\Factories;

use App\Models\TdxPublicWifiCircuit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TdxPublicWifiCircuit>
 */
class TdxPublicWifiCircuitFactory extends Factory
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
            'tdx_asset_id' => fake()->unique()->numerify('TDX-#####'),
            'status' => 'Active',
            'location_name' => fake()->company(),
            'address' => fake()->streetAddress(),
            'speed' => fake()->randomElement(['100 Mbps', '250 Mbps', '1 Gbps']),
            'po_number' => fake()->bothify('##-#### LINE ?'),
            'monthly_cost' => $monthlyCost,
            'yearly_cost' => round($monthlyCost * 12, 2),
            'purchase_cost' => 0,
            'notes' => null,
            'assigned_department_code' => null,
            'responsible_division_id' => null,
            'responsible_location_id' => null,
            'gl_code_id' => null,
            'last_synced_at' => now(),
            'raw_payload' => ['id' => fake()->uuid()],
        ];
    }
}
