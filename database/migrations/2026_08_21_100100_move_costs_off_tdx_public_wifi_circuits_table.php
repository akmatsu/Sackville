<?php

use App\Support\FiscalYear;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fiscalYear = FiscalYear::current();

        DB::table('tdx_public_wifi_circuits')
            ->where(function ($query) {
                $query->whereNotNull('monthly_cost')
                    ->orWhereNotNull('yearly_cost')
                    ->orWhereNotNull('purchase_cost');
            })
            ->orderBy('id')
            ->chunkById(100, function ($circuits) use ($fiscalYear) {
                $now = now();

                DB::table('tdx_public_wifi_circuit_costs')->insert($circuits->map(fn ($circuit) => [
                    'tdx_public_wifi_circuit_id' => $circuit->id,
                    'fiscal_year' => $fiscalYear,
                    'monthly_cost' => $circuit->monthly_cost,
                    'yearly_cost' => $circuit->yearly_cost,
                    'purchase_cost' => $circuit->purchase_cost,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });

        Schema::table('tdx_public_wifi_circuits', function (Blueprint $table) {
            $table->dropColumn(['monthly_cost', 'yearly_cost', 'purchase_cost']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tdx_public_wifi_circuits', function (Blueprint $table) {
            $table->decimal('monthly_cost', 10, 2)->nullable();
            $table->decimal('yearly_cost', 10, 2)->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
        });
    }
};
