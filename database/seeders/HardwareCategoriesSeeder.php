<?php

namespace Database\Seeders;

use App\Models\HardwareCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareCategoriesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the hardware categories.
     */
    public function run(): void
    {
        HardwareCategory::firstOrCreate(['name' => 'Workstation']);
        HardwareCategory::firstOrCreate(['name' => 'Mobile']);
    }
}
