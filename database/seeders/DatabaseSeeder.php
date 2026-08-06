<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            HardwareVendorsSeeder::class,
            HardwareCategoriesSeeder::class,
            HardwareModelsWorkstationSeeder::class,
            HardwareModelsMobileSeeder::class,
            HardwareModelCostsSeeder::class,
            HardwareReplacementGroupsSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
