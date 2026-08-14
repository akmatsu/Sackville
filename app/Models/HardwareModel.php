<?php

namespace App\Models;

use Database\Factories\HardwareModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<HardwareCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(HardwareCategory::class, 'hardware_category_id');
    }

    /**
     * @return HasMany<HardwareModelCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(HardwareModelCost::class);
    }

    /**
     * @return BelongsToMany<HardwareReplacementGroup, $this>
     */
    public function hardwareReplacementGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            HardwareReplacementGroup::class,
            'hardware_replacement_eligible_models',
            'hardware_model_id',
            'hardware_replacement_group_id'
        );
    }
}
