<?php

namespace App\Models;

use Database\Factories\SubObjectCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubObjectCode extends Model
{
    /** @use HasFactory<SubObjectCodeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'object_code',
        'code',
        'name',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function objectCode(): BelongsTo
    {
        return $this->belongsTo(ObjectCode::class, 'object_code', 'code');
    }

    public function glCodes(): HasMany
    {
        return $this->hasMany(GlCode::class);
    }
}
