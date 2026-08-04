<?php

namespace Database\Factories;

use App\Models\HardwareReplacementGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardwareReplacementGroup>
 */
class HardwareReplacementGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'active' => true,
        ];
    }
}
