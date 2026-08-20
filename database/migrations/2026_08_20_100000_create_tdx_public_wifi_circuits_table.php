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
        Schema::create('tdx_public_wifi_circuits', function (Blueprint $table) {
            $table->id();
            $table->string('tdx_asset_id')->unique();
            $table->string('status')->nullable();
            $table->string('location_name')->nullable();
            $table->string('address')->nullable();
            $table->string('speed')->nullable();
            $table->string('po_number')->nullable();
            $table->decimal('monthly_cost', 10, 2)->nullable();
            $table->decimal('yearly_cost', 10, 2)->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('assigned_department_code')->nullable();
            $table->foreignId('responsible_division_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('responsible_location_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('gl_code_id')->nullable()->constrained('gl_codes')->nullOnDelete();
            $table->dateTime('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('assigned_department_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tdx_public_wifi_circuits');
    }
};
