<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_cycles', function (Blueprint $table) {
            $table->id();
            $table->integer('fiscal_year');
            $table->date('opens_at');
            $table->date('closes_at');
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->timestamps();

            $table->unique('fiscal_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_cycles');
    }
};
