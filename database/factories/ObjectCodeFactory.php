<?php

namespace Database\Factories;

use App\Models\ObjectCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObjectCode>
 */
class ObjectCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('###'),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['wages', 'travel', 'supplies', 'equipment', 'software', 'contractual', 'other']),
            'active' => true,
        ];
    }
}
