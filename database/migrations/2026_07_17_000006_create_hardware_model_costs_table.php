<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_model_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hardware_model_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('fiscal_year');
            $table->decimal('unit_cost', 10, 2);
            $table->boolean('with_docking')->default(false);
            $table->decimal('docking_upcharge', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['hardware_model_id', 'fiscal_year', 'with_docking']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_model_costs');
    }
};
