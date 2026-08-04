<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $department = Department::factory()->create();

        return [
            'title' => fake()->jobTitle(),
            'department_code' => $department->code,
            'division_id' => Division::factory()->create(['department_code' => $department->code])->id,
        ];
    }
}
