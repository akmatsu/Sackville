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
        Schema::create('tdx_metronet_circuit_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tdx_metronet_circuit_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('fiscal_year');
            $table->decimal('monthly_cost', 10, 2)->nullable();
            $table->decimal('yearly_cost', 10, 2)->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['tdx_metronet_circuit_id', 'fiscal_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdx_metronet_circuit_costs');
    }
};
