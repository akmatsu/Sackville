<?php

namespace App\Models;

use Database\Factories\HardwareCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'default_object_code',
        'default_sub_object_code_id',
    ];

    /**
     * @return HasMany<HardwareModel, $this>
     */
    public function hardwareModels(): HasMany
    {
        return $this->hasMany(HardwareModel::class);
    }

    /**
     * @return BelongsTo<ObjectCode, $this>
     */
    public function defaultObjectCode(): BelongsTo
    {
        return $this->belongsTo(ObjectCode::class, 'default_object_code', 'code');
    }

    /**
     * @return BelongsTo<SubObjectCode, $this>
     */
    public function defaultSubObjectCode(): BelongsTo
    {
        return $this->belongsTo(SubObjectCode::class, 'default_sub_object_code_id');
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
