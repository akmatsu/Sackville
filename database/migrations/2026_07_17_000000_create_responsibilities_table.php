<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('scope_type', [
                'fund',
                'department',
                'division',
                'object',
                'specific_gl',
            ]);
            $table->string('scope_value');
            $table->enum('role', ['view', 'edit', 'admin']);
            $table->timestamps();

            $table->unique(['user_id', 'scope_type', 'scope_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsibilities');
    }
};
