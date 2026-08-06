<?php

namespace Database\Seeders;

use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareModelsWorkstationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the workstation hardware models.
     */
    public function run(): void
    {
        $dellVendor = Vendor::where('name', 'Dell')->first();
        $otherVendor = Vendor::where('name', 'Other')->first();
        $workstationCategory = HardwareCategory::where('name', 'Workstation')->first();

        $models = [
            ['name' => 'Standard Desktop', 'vendor_id' => $dellVendor->id],
            ['name' => 'GIS Precision Desktop', 'vendor_id' => $dellVendor->id],
            ['name' => 'GIS Precision Laptop', 'vendor_id' => $dellVendor->id],
            ['name' => 'GIS Precision Laptop w Docking Station', 'vendor_id' => $dellVendor->id],
            ['name' => 'Large Precision Laptop', 'vendor_id' => $dellVendor->id],
            ['name' => 'Large Precision Laptop w Docking Station', 'vendor_id' => $dellVendor->id],
            ['name' => 'Standard Latitude Laptop', 'vendor_id' => $dellVendor->id],
            ['name' => 'Standard Latitude Laptop w Docking Station', 'vendor_id' => $dellVendor->id],
            ['name' => 'Other - Please contact Service Desk', 'vendor_id' => $otherVendor->id],
            ['name' => '2-in-1 Laptop', 'vendor_id' => $otherVendor->id],
            ['name' => 'Rugged Laptop', 'vendor_id' => $otherVendor->id],
        ];

        foreach ($models as $model) {
            HardwareModel::firstOrCreate(
                ['vendor_id' => $model['vendor_id'], 'name' => $model['name']],
                ['hardware_category_id' => $workstationCategory->id, 'active' => true]
            );
        }
    }
}
