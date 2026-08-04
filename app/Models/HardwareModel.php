<?php

namespace App\Models;

use Database\Factories\HardwareModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HardwareModel extends Model
{
    /** @use HasFactory<HardwareModelFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'hardware_category_id',
        'name',
        'specs',
        'has_docking_option',
        'active',
    ];

    protected $casts = [
        'has_docking_option' => 'boolean',
        'active' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HardwareCategory::class, 'hardware_category_id');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(HardwareModelCost::class);
    }
}
