<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_categories', function (Blueprint $table) {
            $table->string('default_object_code')->nullable()->after('name');
            $table->foreign('default_object_code')->references('code')->on('object_codes')->nullOnDelete();
            $table->foreignId('default_sub_object_code_id')->nullable()->after('default_object_code')->constrained('sub_object_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hardware_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_sub_object_code_id');
            $table->dropForeign(['default_object_code']);
            $table->dropColumn('default_object_code');
        });
    }
};
