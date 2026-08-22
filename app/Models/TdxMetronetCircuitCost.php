<?php

namespace App\Models;

use Database\Factories\TdxMetronetCircuitCostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdxMetronetCircuitCost extends Model
{
    /** @use HasFactory<TdxMetronetCircuitCostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tdx_metronet_circuit_id',
        'fiscal_year',
        'monthly_cost',
        'yearly_cost',
        'purchase_cost',
    ];

    /**
     * @return BelongsTo<TdxMetronetCircuit, $this>
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(TdxMetronetCircuit::class, 'tdx_metronet_circuit_id');
    }
}
