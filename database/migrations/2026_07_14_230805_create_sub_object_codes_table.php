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
        Schema::create('sub_object_codes', function (Blueprint $table) {
            $table->id();
            $table->string('object_code');
            $table->foreign('object_code')->references('code')->on('object_codes')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['object_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_object_codes');
    }
};
