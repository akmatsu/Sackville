<?php

namespace Database\Factories;

use App\Models\SoftwareLicense;
use App\Models\SoftwareProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareLicense>
 */
class SoftwareLicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $count = fake()->numberBetween(1, 50);
        $unitCost = fake()->randomFloat(2, 10, 500);

        return [
            'software_product_id' => SoftwareProduct::factory(),
            'fiscal_year' => fake()->numberBetween(26, 30),
            'license_count' => $count,
            'unit_cost' => $unitCost,
            'total_cost' => $count * $unitCost,
            'license_expiration' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'license_notes' => fake()->optional()->sentence(),
            'justification' => fake()->optional()->sentence(),
        ];
    }
}
