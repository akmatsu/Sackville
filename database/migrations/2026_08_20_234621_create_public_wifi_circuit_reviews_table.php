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
        Schema::create('public_wifi_circuit_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_cycle_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tdx_public_wifi_circuit_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->boolean('still_needed');
            $table->text('justification')->nullable();
            $table->foreignId('reviewed_by_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['budget_cycle_id', 'tdx_public_wifi_circuit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_wifi_circuit_reviews');
    }
};
