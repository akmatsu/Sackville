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
        Schema::create('hardware_replacement_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_cycle_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('tdx_asset_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('hardware_model_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->boolean('with_docking')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('selected_by_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['budget_cycle_id', 'tdx_asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hardware_replacement_selections');
    }
};
