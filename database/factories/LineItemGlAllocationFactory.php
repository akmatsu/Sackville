<?php

namespace Database\Factories;

use App\Models\BudgetLineItem;
use App\Models\GlCode;
use App\Models\LineItemGlAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LineItemGlAllocation>
 */
class LineItemGlAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_line_item_id' => BudgetLineItem::factory(),
            'gl_code_id' => GlCode::factory(),
            'percent' => 100,
            'amount' => fake()->randomFloat(2, 200, 3000),
        ];
    }
}
