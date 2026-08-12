<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tdx_assets', function (Blueprint $table) {
            $table->boolean('has_docking_station')->nullable()->after('hardware_model_id');
        });
    }

    public function down(): void
    {
        Schema::table('tdx_assets', function (Blueprint $table) {
            $table->dropColumn('has_docking_station');
        });
    }
};
