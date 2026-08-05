<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('integration')->unique();
            $table->string('frequency');
            $table->string('time_of_day', 5)->nullable();
            $table->unsignedTinyInteger('interval_hours')->nullable();
            $table->string('timezone');
            $table->timestamps();
        });

        DB::table('sync_schedules')->insert([
            'integration' => 'tdx',
            'frequency' => config('tdx.hardware_sync.frequency'),
            'time_of_day' => config('tdx.hardware_sync.time_of_day'),
            'interval_hours' => config('tdx.hardware_sync.interval_hours'),
            'timezone' => config('tdx.hardware_sync.timezone'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_schedules');
    }
};
