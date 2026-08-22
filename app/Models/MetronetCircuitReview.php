<?php

namespace App\Models;

use Database\Factories\MetronetCircuitReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetronetCircuitReview extends Model
{
    /** @use HasFactory<MetronetCircuitReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'budget_cycle_id',
        'tdx_metronet_circuit_id',
        'still_needed',
        'justification',
        'reviewed_by_id',
    ];

    protected $casts = [
        'still_needed' => 'boolean',
    ];

    /**
     * @return BelongsTo<BudgetCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BudgetCycle::class, 'budget_cycle_id');
    }

    /**
     * @return BelongsTo<TdxMetronetCircuit, $this>
     */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(TdxMetronetCircuit::class, 'tdx_metronet_circuit_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
