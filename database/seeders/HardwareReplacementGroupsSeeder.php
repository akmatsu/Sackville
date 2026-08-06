<?php

namespace Database\Seeders;

use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\HardwareReplacementGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareReplacementGroupsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Names of the budget-catalog models eligible under the Workstation group.
     *
     * @var list<string>
     */
    private const WORKSTATION_MODEL_NAMES = [
        'Standard Desktop',
        'GIS Precision Desktop',
        'GIS Precision Laptop',
        'GIS Precision Laptop w Docking Station',
        'Large Precision Laptop',
        'Large Precision Laptop w Docking Station',
        'Standard Latitude Laptop',
        'Standard Latitude Laptop w Docking Station',
        'Other - Please contact Service Desk',
        '2-in-1 Laptop',
        'Rugged Laptop',
    ];

    /**
     * Names of the budget-catalog models eligible under the Mobile group.
     *
     * @var list<string>
     */
    private const MOBILE_MODEL_NAMES = [
        'Standard iPad (no data plan)',
        'Standard iPad (w/data plan)',
        'Large iPad (no data plan)',
        'Large iPad (w/data plan)',
        'Cradlepoint',
        'Standard iPhone',
        'Other - Please contact Service Desk',
    ];

    /**
     * Seed the hardware replacement groups.
     */
    public function run(): void
    {
        // Create Workstation replacement group
        $workstationGroup = HardwareReplacementGroup::firstOrCreate(
            ['name' => 'Workstation'],
            ['active' => true]
        );

        $workstationCategory = HardwareCategory::where('name', 'Workstation')->first();
        $workstationModels = HardwareModel::whereIn('name', self::WORKSTATION_MODEL_NAMES)->pluck('id');

        $workstationGroup->replaceableCategories()->syncWithoutDetaching($workstationCategory->id);
        $workstationGroup->eligibleModels()->sync($workstationModels);

        // Create Mobile replacement group
        $mobileGroup = HardwareReplacementGroup::firstOrCreate(
            ['name' => 'Mobile'],
            ['active' => true]
        );

        $mobileCategory = HardwareCategory::where('name', 'Mobile')->first();
        $mobileModels = HardwareModel::whereIn('name', self::MOBILE_MODEL_NAMES)->pluck('id');

        $mobileGroup->replaceableCategories()->syncWithoutDetaching($mobileCategory->id);
        $mobileGroup->eligibleModels()->sync($mobileModels);
    }
}
