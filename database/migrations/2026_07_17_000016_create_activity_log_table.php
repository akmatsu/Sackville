<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['create', 'update', 'delete']);
            $table->json('diff')->nullable();
            $table->foreignId('actor_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->dateTime('at')->useCurrent();
            $table->timestamps();

            $table->index(['table_name', 'record_id']);
            $table->index('actor_id');
            $table->index('at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
