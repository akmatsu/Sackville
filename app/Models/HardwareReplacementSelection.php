<?php

namespace App\Models;

use Database\Factories\HardwareReplacementSelectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardwareReplacementSelection extends Model
{
    /** @use HasFactory<HardwareReplacementSelectionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'budget_cycle_id',
        'tdx_asset_id',
        'hardware_model_id',
        'opted_out',
        'with_docking',
        'notes',
        'selected_by_id',
    ];

    protected $casts = [
        'opted_out' => 'boolean',
        'with_docking' => 'boolean',
    ];

    /**
     * @return BelongsTo<BudgetCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BudgetCycle::class, 'budget_cycle_id');
    }

    /**
     * @return BelongsTo<TdxAsset, $this>
     */
    public function tdxAsset(): BelongsTo
    {
        return $this->belongsTo(TdxAsset::class);
    }

    /**
     * @return BelongsTo<HardwareModel, $this>
     */
    public function hardwareModel(): BelongsTo
    {
        return $this->belongsTo(HardwareModel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by_id');
    }
}
