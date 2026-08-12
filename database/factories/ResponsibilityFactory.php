<?php

namespace Database\Factories;

use App\Models\Responsibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Responsibility>
 */
class ResponsibilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scope_type' => 'department',
            'scope_value' => fake()->numerify('###'),
            'responsible_division_id' => null,
            'responsible_location_id' => null,
            'role' => 'view',
        ];
    }
}
