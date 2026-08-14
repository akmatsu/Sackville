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
        Schema::table('responsibilities', function (Blueprint $table) {
            $table->string('scope_value')->nullable()->change();

            $table->foreignId('responsible_division_id')
                ->nullable()
                ->after('scope_value')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('responsible_location_id')
                ->nullable()
                ->after('responsible_division_id')
                ->constrained()
                ->nullOnDelete();

            $table->unique(['user_id', 'responsible_division_id']);
            $table->unique(['user_id', 'responsible_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responsibilities', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'responsible_division_id']);
            $table->dropUnique(['user_id', 'responsible_location_id']);

            $table->dropForeign(['responsible_division_id']);
            $table->dropForeign(['responsible_location_id']);
            $table->dropColumn(['responsible_division_id', 'responsible_location_id']);

            $table->string('scope_value')->nullable(false)->change();
        });
    }
};
