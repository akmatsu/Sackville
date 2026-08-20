<?php

namespace Database\Factories;

use App\Models\BudgetCycle;
use App\Models\PublicWifiCircuitReview;
use App\Models\TdxPublicWifiCircuit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicWifiCircuitReview>
 */
class PublicWifiCircuitReviewFactory extends Factory
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
            'tdx_public_wifi_circuit_id' => TdxPublicWifiCircuit::factory(),
            'still_needed' => true,
            'justification' => null,
            'reviewed_by_id' => User::factory(),
        ];
    }
}
