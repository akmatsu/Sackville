<?php

namespace App\Models;

use Database\Factories\HardwareCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function hardwareModels(): HasMany
    {
        return $this->hasMany(HardwareModel::class);
    }
}
