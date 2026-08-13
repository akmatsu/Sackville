<?php

use App\Enums\ResponsibilityScopeType;
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
        Schema::table('responsibilities', function (Blueprint $table): void {
            $table->enum('scope_type', array_column(ResponsibilityScopeType::cases(), 'value'))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responsibilities', function (Blueprint $table): void {
            $table->enum('scope_type', [
                'fund',
                'department',
                'division',
                'object',
                'specific_gl',
            ])->change();
        });
    }
};
