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
            'integration' => 'tdx-metronet',
            'frequency' => config('tdx.metronet_sync.frequency'),
            'time_of_day' => config('tdx.metronet_sync.time_of_day'),
            'interval_hours' => config('tdx.metronet_sync.interval_hours'),
            'timezone' => config('tdx.metronet_sync.timezone'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sync_schedules')->where('integration', 'tdx-metronet')->delete();
    }
};
