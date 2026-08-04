<?php

namespace Database\Factories;

use App\Models\SoftwareProduct;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareProduct>
 */
class SoftwareProductFactory extends Factory
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
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'default_license_type' => fake()->randomElement(['per_seat', 'site', 'concurrent']),
            'billing_frequency' => fake()->randomElement(['monthly', 'annual', 'one_time']),
            'url' => fake()->url(),
            'active' => true,
        ];
    }
}
