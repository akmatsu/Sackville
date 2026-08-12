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
            $table->dropForeign(['assigned_division_id']);
            $table->dropColumn(['assigned_division_id', 'assigned_location_name']);

            $table->foreignId('responsible_division_id')
                ->nullable()
                ->after('assigned_department_code')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('responsible_location_id')
                ->nullable()
                ->after('responsible_division_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tdx_assets', function (Blueprint $table) {
            $table->dropForeign(['responsible_division_id']);
            $table->dropForeign(['responsible_location_id']);
            $table->dropColumn(['responsible_division_id', 'responsible_location_id']);

            $table->foreignId('assigned_division_id')
                ->nullable()
                ->constrained('divisions')
                ->nullOnDelete();
            $table->string('assigned_location_name')->nullable();
        });
    }
};
