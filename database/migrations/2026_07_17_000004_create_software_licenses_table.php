<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('fiscal_year');
            $table->integer('license_count')->default(1);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->date('license_expiration')->nullable();
            $table->text('license_notes')->nullable();
            $table->text('justification')->nullable();
            $table->timestamps();

            $table->unique(['software_product_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_licenses');
    }
};
