<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('hardware_category_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('specs')->nullable();
            $table->boolean('has_docking_option')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['vendor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_models');
    }
};
