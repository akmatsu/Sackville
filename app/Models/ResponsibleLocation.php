<?php

namespace App\Models;

use Database\Factories\ResponsibleLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponsibleLocation extends Model
{
    /** @use HasFactory<ResponsibleLocationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'responsible_division_id',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(ResponsibleDivision::class, 'responsible_division_id');
    }

    public function tdxAssets(): HasMany
    {
        return $this->hasMany(TdxAsset::class);
    }

    public function responsibilities(): HasMany
    {
        return $this->hasMany(Responsibility::class);
    }
}
