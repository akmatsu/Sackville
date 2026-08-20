<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('sync_schedules')->insert([
            'integration' => 'tdx-public-wifi',
            'frequency' => config('tdx.public_wifi_sync.frequency'),
            'time_of_day' => config('tdx.public_wifi_sync.time_of_day'),
            'interval_hours' => config('tdx.public_wifi_sync.interval_hours'),
            'timezone' => config('tdx.public_wifi_sync.timezone'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sync_schedules')->where('integration', 'tdx-public-wifi')->delete();
    }
};
