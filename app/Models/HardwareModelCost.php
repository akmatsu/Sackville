<?php

namespace App\Models;

use Database\Factories\HardwareModelCostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardwareModelCost extends Model
{
    /** @use HasFactory<HardwareModelCostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hardware_model_id',
        'fiscal_year',
        'unit_cost',
        'with_docking',
        'docking_upcharge',
    ];

    protected $casts = [
        'with_docking' => 'boolean',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(HardwareModel::class);
    }
}
