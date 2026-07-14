<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_code' => Department::factory(),
            'code' => fake()->unique()->numerify('###'),
            'name' => fake()->words(2, true),
            'active' => true,
        ];
    }
}
