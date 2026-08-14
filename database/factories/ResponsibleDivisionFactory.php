<?php

namespace Database\Factories;

use App\Models\ResponsibleDivision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResponsibleDivision>
 */
class ResponsibleDivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_name' => fake()->company(),
            'name' => fake()->unique()->words(2, true),
            'active' => true,
        ];
    }
}
