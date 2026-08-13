<?php

namespace App\Models;

use Database\Factories\ResponsibleDivisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponsibleDivision extends Model
{
    /** @use HasFactory<ResponsibleDivisionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'department_name',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @return HasMany<ResponsibleLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(ResponsibleLocation::class);
    }

    /**
     * @return HasMany<TdxAsset, $this>
     */
    public function tdxAssets(): HasMany
    {
        return $this->hasMany(TdxAsset::class);
    }

    /**
     * @return HasMany<Responsibility, $this>
     */
    public function responsibilities(): HasMany
    {
        return $this->hasMany(Responsibility::class);
    }
}
