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
            $table->string('assigned_location_name')->nullable();
            $table->foreignId('gl_code_id')->nullable()->constrained('gl_codes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tdx_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gl_code_id');
            $table->dropColumn('assigned_location_name');
        });
    }
};
