<?php

namespace Database\Factories;

use App\Models\ResponsibleDivision;
use App\Models\ResponsibleLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResponsibleLocation>
 */
class ResponsibleLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'responsible_division_id' => ResponsibleDivision::factory(),
            'name' => fake()->unique()->city(),
            'active' => true,
        ];
    }
}
