<?php

namespace Database\Seeders;

use App\Models\HardwareCategory;
use App\Models\HardwareModel;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareModelsMobileSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the mobile hardware models.
     */
    public function run(): void
    {
        $otherVendor = Vendor::where('name', 'Other')->first();
        $mobileCategory = HardwareCategory::where('name', 'Mobile')->first();

        $models = [
            ['name' => 'Standard iPad (no data plan)', 'vendor_id' => $otherVendor->id],
            ['name' => 'Standard iPad (w/data plan)', 'vendor_id' => $otherVendor->id],
            ['name' => 'Large iPad (no data plan)', 'vendor_id' => $otherVendor->id],
            ['name' => 'Large iPad (w/data plan)', 'vendor_id' => $otherVendor->id],
            ['name' => 'Cradlepoint', 'vendor_id' => $otherVendor->id],
            ['name' => 'Standard iPhone', 'vendor_id' => $otherVendor->id],
            ['name' => 'Other - Please contact Service Desk', 'vendor_id' => $otherVendor->id],
        ];

        foreach ($models as $model) {
            HardwareModel::firstOrCreate(
                ['vendor_id' => $model['vendor_id'], 'name' => $model['name']],
                ['hardware_category_id' => $mobileCategory->id, 'active' => true]
            );
        }
    }
}
