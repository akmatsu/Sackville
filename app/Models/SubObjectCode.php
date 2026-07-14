<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubObjectCode extends Model
{
    /** @use HasFactory<\Database\Factories\SubObjectCodeFactory> */
    use HasFactory;

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
