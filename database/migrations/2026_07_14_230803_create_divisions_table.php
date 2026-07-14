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
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('department_code');
            $table->foreign('department_code')->references('code')->on('departments')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['department_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
