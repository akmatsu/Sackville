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
            $table->string('source')->nullable()->after('tdx_asset_id')->index();
            $table->string('product_type')->nullable()->after('status');
            $table->string('plan_serial')->nullable()->after('serial')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tdx_assets', function (Blueprint $table) {
            $table->dropColumn(['source', 'product_type', 'plan_serial']);
        });
    }
};
