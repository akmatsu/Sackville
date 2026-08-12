<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_replacement_selections', function (Blueprint $table) {
            $table->foreignId('hardware_model_id')->nullable()->change();
            $table->boolean('opted_out')->default(false)->after('hardware_model_id');
        });
    }

    public function down(): void
    {
        Schema::table('hardware_replacement_selections', function (Blueprint $table) {
            $table->dropColumn('opted_out');
            $table->foreignId('hardware_model_id')->nullable(false)->change();
        });
    }
};
