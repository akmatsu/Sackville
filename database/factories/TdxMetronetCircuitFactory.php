<?php

namespace Database\Factories;

use App\Models\TdxMetronetCircuit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TdxMetronetCircuit>
 */
class TdxMetronetCircuitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tdx_asset_id' => fake()->unique()->numerify('TDX-#####'),
            'status' => 'Active',
            'location_name' => fake()->company(),
            'circuit_number' => 'MNET'.fake()->numerify('####.##'),
            'speed' => fake()->randomElement(['100 Mbps', '250 Mbps', '1 Gig']),
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
