<?php

namespace Database\Factories;

use App\Models\BudgetCycle;
use App\Models\BudgetLineItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetLineItem>
 */
class BudgetLineItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_cycle_id' => BudgetCycle::factory(),
            'item_type' => 'hardware_replacement',
            'tdx_asset_id' => null,
            'hardware_model_id' => null,
            'software_product_id' => null,
            'with_docking' => false,
            'quantity' => 1,
            'previous_cost' => fake()->randomFloat(2, 200, 3000),
            'proposed_cost' => fake()->randomFloat(2, 200, 3000),
            'description' => fake()->sentence(),
            'justification' => fake()->optional()->sentence(),
            'status' => 'not_started',
            'created_by_id' => User::factory(),
            'last_modified_by_id' => null,
        ];
    }
}
