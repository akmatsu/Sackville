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
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->string('network_source')->nullable()->after('item_type');
        });

        // Every existing 'network' line item was created from the public
        // wifi review page — Metronet requests are new as of this migration.
        DB::table('budget_line_items')
            ->where('item_type', 'network')
            ->update(['network_source' => 'public_wifi']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_line_items', function (Blueprint $table) {
            $table->dropColumn('network_source');
        });
    }
};
