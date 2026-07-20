<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_item_gl_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_line_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('gl_code_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('percent', 5, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['budget_line_item_id', 'gl_code_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_item_gl_allocations');
    }
};
