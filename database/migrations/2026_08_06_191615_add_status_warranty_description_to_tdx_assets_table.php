<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tdx_assets', function (Blueprint $table) {
            $table->string('status')->nullable()->after('tdx_asset_id');
            $table->string('description')->nullable()->after('status');
            $table->date('warranty_ends_at')->nullable()->after('fy_replacement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tdx_assets', function (Blueprint $table) {
            $table->dropColumn(['status', 'description', 'warranty_ends_at']);
        });
    }
};
