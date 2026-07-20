<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('integration');
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->integer('records_synced')->default(0);
            $table->integer('records_failed')->default(0);
            $table->enum('status', ['success', 'partial', 'failed']);
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index('integration');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
