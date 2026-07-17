<?php

namespace Database\Factories;

use App\Enums\FundType;
use App\Models\Fund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fund>
 */
class FundFactory extends Factory
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
            'fund_type' => fake()->randomElement(FundType::cases()),
            'active' => true,
        ];
    }
}
