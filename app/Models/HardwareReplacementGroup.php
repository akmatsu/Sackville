<?php

namespace App\Models;

use Database\Factories\HardwareReplacementGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HardwareReplacementGroup extends Model
{
    /** @use HasFactory<HardwareReplacementGroupFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<HardwareCategory, $this>
     */
    public function replaceableCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            HardwareCategory::class,
            'hardware_replacement_replaceable_categories',
            'hardware_replacement_group_id',
            'hardware_category_id'
        );
    }

    /**
     * @return BelongsToMany<HardwareModel, $this>
     */
    public function eligibleModels(): BelongsToMany
    {
        return $this->belongsToMany(
            HardwareModel::class,
            'hardware_replacement_eligible_models',
            'hardware_replacement_group_id',
            'hardware_model_id'
        );
    }
}
