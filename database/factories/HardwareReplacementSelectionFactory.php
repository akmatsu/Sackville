<?php

namespace Database\Factories;

use App\Models\BudgetCycle;
use App\Models\HardwareModel;
use App\Models\HardwareReplacementSelection;
use App\Models\TdxAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HardwareReplacementSelection>
 */
class HardwareReplacementSelectionFactory extends Factory
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
            'tdx_asset_id' => TdxAsset::factory(),
            'hardware_model_id' => HardwareModel::factory(),
            'with_docking' => false,
            'notes' => null,
            'selected_by_id' => User::factory(),
        ];
    }
}
