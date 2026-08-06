<?php

namespace Database\Factories;

use App\Models\TdxAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TdxAsset>
 */
class TdxAssetFactory extends Factory
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
            'status' => 'Production',
            'description' => fake()->name(),
            'asset_tag' => fake()->unique()->bothify('AT-#####'),
            'serial' => fake()->unique()->bothify('SN########'),
            'hardware_model_id' => null,
            'assigned_user_upn' => fake()->userName().'@matsu.gov',
            'assigned_department_code' => null,
            'assigned_division_id' => null,
            'assigned_location_name' => null,
            'gl_code_id' => null,
            'acquired_at' => fake()->dateTimeBetween('-5 years', 'now'),
            'fy_replacement' => fake()->numberBetween(27, 32),
            'warranty_ends_at' => fake()->dateTimeBetween('now', '+3 years'),
            'last_synced_at' => now(),
            'raw_payload' => ['id' => fake()->uuid()],
        ];
    }
}
