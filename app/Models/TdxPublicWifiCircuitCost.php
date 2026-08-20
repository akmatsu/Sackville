<?php

namespace App\Models;

use Database\Factories\TdxPublicWifiCircuitCostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdxPublicWifiCircuitCost extends Model
{
    /** @use HasFactory<TdxPublicWifiCircuitCostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tdx_public_wifi_circuit_id',
        'fiscal_year',
        'monthly_cost',
        'yearly_cost',
        'purchase_cost',
    ];

    /**
     * @return BelongsTo<TdxPublicWifiCircuit, $this>
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(TdxPublicWifiCircuit::class, 'tdx_public_wifi_circuit_id');
    }
}
