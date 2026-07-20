<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_cycle_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('item_type', [
                'hardware_replacement',
                'hardware_addition',
                'software',
                'network',
                'other',
            ]);
            $table->foreignId('tdx_asset_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('hardware_model_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('software_product_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->boolean('with_docking')->default(false);
            $table->integer('quantity')->default(1);
            $table->decimal('previous_cost', 10, 2)->nullable();
            $table->decimal('proposed_cost', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->text('justification')->nullable();
            $table->enum('status', [
                'not_started',
                'in_progress',
                'complete',
                'declined',
            ])->default('not_started');
            $table->foreignId('created_by_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('last_modified_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('budget_cycle_id');
            $table->index('created_by_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_line_items');
    }
};
