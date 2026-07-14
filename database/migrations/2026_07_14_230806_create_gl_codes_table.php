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
        Schema::create('gl_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_string')->unique();

            $table->string('fund_code');
            $table->foreign('fund_code')->references('code')->on('funds')->cascadeOnDelete();

            $table->string('department_code');
            $table->foreign('department_code')->references('code')->on('departments')->cascadeOnDelete();

            $table->foreignId('division_id')->nullable()->constrained('divisions')->cascadeOnDelete();

            $table->string('object_code')->nullable();
            $table->foreign('object_code')->references('code')->on('object_codes')->cascadeOnDelete();

            $table->foreignId('sub_object_code_id')->nullable()->constrained('sub_object_codes')->cascadeOnDelete();

            $table->string('label');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_codes');
    }
};
