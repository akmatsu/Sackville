<?php

namespace Database\Factories;

use App\Models\TdxMobilePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TdxMobilePlan>
 */
class TdxMobilePlanFactory extends Factory
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
            'carrier' => fake()->randomElement(['AT&T', 'Verizon', 'T-Mobile']),
            'po_number' => fake()->bothify('##-####'),
            'plan_status' => 'Active',
            'plan_description' => fake()->sentence(),
            'description' => fake()->name(),
            'asset_tag' => fake()->unique()->bothify('AT-#####'),
            'serial' => fake()->unique()->numerify('##########'),
            'assigned_user_upn' => fake()->userName().'@matsu.gov',
            'assigned_department_code' => null,
            'responsible_division_id' => null,
            'responsible_location_id' => null,
            'gl_code_id' => null,
            'last_synced_at' => now(),
            'raw_payload' => ['id' => fake()->uuid()],
        ];
    }
}
