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
        Schema::create('tdx_mobile_plans', function (Blueprint $table) {
            $table->id();
            $table->string('tdx_asset_id')->unique();
            $table->string('status')->nullable();
            $table->string('carrier')->nullable();
            $table->string('po_number')->nullable();
            $table->string('plan_status')->nullable();
            $table->text('plan_description')->nullable();
            $table->string('description')->nullable();
            $table->string('asset_tag')->nullable();
            $table->string('serial')->nullable()->index();
            $table->string('assigned_user_upn')->nullable();
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
        Schema::dropIfExists('tdx_mobile_plans');
    }
};
