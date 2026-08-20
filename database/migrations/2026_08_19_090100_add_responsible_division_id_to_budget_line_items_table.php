<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->foreignId('responsible_division_id')
                ->nullable()
                ->after('budget_cycle_id')
                ->constrained('responsible_divisions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_division_id');
        });
    }
};
