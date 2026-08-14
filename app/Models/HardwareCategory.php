<?php

namespace App\Models;

use Database\Factories\HardwareCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HardwareCategory extends Model
{
    /** @use HasFactory<HardwareCategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return HasMany<HardwareModel, $this>
     */
    public function hardwareModels(): HasMany
    {
        return $this->hasMany(HardwareModel::class);
    }

    /**
     * @return BelongsToMany<HardwareReplacementGroup, $this>
     */
    public function hardwareReplacementGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            HardwareReplacementGroup::class,
            'hardware_replacement_replaceable_categories',
            'hardware_category_id',
            'hardware_replacement_group_id'
        );
    }
}
