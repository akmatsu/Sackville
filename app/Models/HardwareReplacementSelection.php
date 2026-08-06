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
        'with_docking',
        'notes',
        'selected_by_id',
    ];

    protected $casts = [
        'with_docking' => 'boolean',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BudgetCycle::class, 'budget_cycle_id');
    }

    public function tdxAsset(): BelongsTo
    {
        return $this->belongsTo(TdxAsset::class);
    }

    public function hardwareModel(): BelongsTo
    {
        return $this->belongsTo(HardwareModel::class);
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by_id');
    }
}
