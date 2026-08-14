<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareVendorsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the hardware vendors.
     */
    public function run(): void
    {
        Vendor::firstOrCreate(
            ['name' => 'Dell'],
            ['active' => true]
        );

        Vendor::firstOrCreate(
            ['name' => 'Other'],
            ['active' => true]
        );
    }
}
