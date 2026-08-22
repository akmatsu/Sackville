<?php

namespace Database\Factories;

use App\Models\BudgetCycle;
use App\Models\MetronetCircuitReview;
use App\Models\TdxMetronetCircuit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetronetCircuitReview>
 */
class MetronetCircuitReviewFactory extends Factory
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
            'tdx_metronet_circuit_id' => TdxMetronetCircuit::factory(),
            'still_needed' => true,
            'justification' => null,
            'reviewed_by_id' => User::factory(),
        ];
    }
}
