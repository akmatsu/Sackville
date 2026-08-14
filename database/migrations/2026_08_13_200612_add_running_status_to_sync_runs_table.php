<?php

use App\Enums\SyncRunStatus;
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
        Schema::table('sync_runs', function (Blueprint $table): void {
            $table->enum('status', array_column(SyncRunStatus::cases(), 'value'))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_runs', function (Blueprint $table): void {
            $table->enum('status', [
                'success',
                'partial',
                'failed',
            ])->change();
        });
    }
};
