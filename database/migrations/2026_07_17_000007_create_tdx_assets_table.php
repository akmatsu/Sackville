<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tdx_assets', function (Blueprint $table) {
            $table->id();
            $table->string('tdx_asset_id')->unique();
            $table->string('asset_tag')->nullable();
            $table->string('serial')->nullable();
            $table->foreignId('hardware_model_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('assigned_user_upn')->nullable();
            $table->string('assigned_department_code')->nullable();
            $table->foreignId('assigned_division_id')
                ->nullable()
                ->constrained('divisions')
                ->nullOnDelete();
            $table->date('acquired_at')->nullable();
            $table->integer('fy_replacement')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('assigned_department_code');
            $table->index('fy_replacement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tdx_assets');
    }
};
