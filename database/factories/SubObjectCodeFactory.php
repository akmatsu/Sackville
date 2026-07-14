<?php

namespace Database\Factories;

use App\Models\ObjectCode;
use App\Models\SubObjectCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubObjectCode>
 */
class SubObjectCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'object_code' => ObjectCode::factory(),
            'code' => fake()->unique()->numerify('###'),
            'name' => fake()->words(2, true),
            'active' => true,
        ];
    }
}
