<?php

namespace Database\Seeders;

use App\Models\HardwareModel;
use App\Models\HardwareModelCost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareModelCostsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the hardware model costs.
     */
    public function run(): void
    {
        $costs = [
            // Workstation FY24 models
            ['model_name' => 'Standard Desktop', 'fiscal_year' => 24, 'unit_cost' => 1580.00],
            ['model_name' => 'GIS Precision Desktop', 'fiscal_year' => 24, 'unit_cost' => 2935.00],
            ['model_name' => 'GIS Precision Laptop', 'fiscal_year' => 24, 'unit_cost' => 4000.00],
            ['model_name' => 'GIS Precision Laptop w Docking Station', 'fiscal_year' => 24, 'unit_cost' => 4325.00],
            ['model_name' => 'Large Precision Laptop', 'fiscal_year' => 24, 'unit_cost' => 1948.00],
            ['model_name' => 'Large Precision Laptop w Docking Station', 'fiscal_year' => 24, 'unit_cost' => 2198.00],
            ['model_name' => 'Standard Latitude Laptop', 'fiscal_year' => 24, 'unit_cost' => 1852.00],
            ['model_name' => 'Standard Latitude Laptop w Docking Station', 'fiscal_year' => 24, 'unit_cost' => 2102.00],
            ['model_name' => 'Other - Please contact Service Desk', 'fiscal_year' => 24, 'unit_cost' => 0.00],
            // Workstation FY25 models
            ['model_name' => '2-in-1 Laptop', 'fiscal_year' => 25, 'unit_cost' => 2516.00],
            ['model_name' => 'Rugged Laptop', 'fiscal_year' => 25, 'unit_cost' => 2550.00],
            // Workstation FY28 models (current open budget cycle catalog)
            ['model_name' => 'Standard Desktop', 'fiscal_year' => 28, 'unit_cost' => 1650.00],
            ['model_name' => 'GIS Precision Desktop', 'fiscal_year' => 28, 'unit_cost' => 3100.00],
            ['model_name' => 'GIS Precision Laptop', 'fiscal_year' => 28, 'unit_cost' => 4200.00],
            ['model_name' => 'GIS Precision Laptop w Docking Station', 'fiscal_year' => 28, 'unit_cost' => 4550.00],
            ['model_name' => 'Large Precision Laptop', 'fiscal_year' => 28, 'unit_cost' => 2050.00],
            ['model_name' => 'Large Precision Laptop w Docking Station', 'fiscal_year' => 28, 'unit_cost' => 2325.00],
            ['model_name' => 'Standard Latitude Laptop', 'fiscal_year' => 28, 'unit_cost' => 1950.00],
            ['model_name' => 'Standard Latitude Laptop w Docking Station', 'fiscal_year' => 28, 'unit_cost' => 2225.00],
            ['model_name' => 'Other - Please contact Service Desk', 'fiscal_year' => 28, 'unit_cost' => 0.00],
            ['model_name' => '2-in-1 Laptop', 'fiscal_year' => 28, 'unit_cost' => 2650.00],
            ['model_name' => 'Rugged Laptop', 'fiscal_year' => 28, 'unit_cost' => 2700.00],
            // Mobile models (fiscal_year 0 = "All")
            ['model_name' => 'Standard iPad (no data plan)', 'fiscal_year' => 0, 'unit_cost' => 1210.00],
            ['model_name' => 'Standard iPad (w/data plan)', 'fiscal_year' => 0, 'unit_cost' => 1610.00],
            ['model_name' => 'Large iPad (no data plan)', 'fiscal_year' => 0, 'unit_cost' => 1430.00],
            ['model_name' => 'Large iPad (w/data plan)', 'fiscal_year' => 0, 'unit_cost' => 1830.00],
            ['model_name' => 'Cradlepoint', 'fiscal_year' => 0, 'unit_cost' => 1600.00],
            ['model_name' => 'Standard iPhone', 'fiscal_year' => 0, 'unit_cost' => 0.99],
        ];

        foreach ($costs as $cost) {
            $model = HardwareModel::where('name', $cost['model_name'])->first();

            if ($model) {
                HardwareModelCost::firstOrCreate(
                    ['hardware_model_id' => $model->id, 'fiscal_year' => $cost['fiscal_year']],
                    ['unit_cost' => $cost['unit_cost'], 'with_docking' => false]
                );
            }
        }
    }
}
