<?php

namespace Database\Factories;

use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardwareModel>
 */
class HardwareModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'hardware_category_id' => HardwareCategory::factory(),
            'name' => fake()->unique()->bothify('Model ###??'),
            'specs' => fake()->optional()->sentence(),
            'has_docking_option' => false,
            'active' => true,
        ];
    }
}
