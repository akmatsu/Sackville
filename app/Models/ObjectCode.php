<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjectCode extends Model
{
    /** @use HasFactory<\Database\Factories\ObjectCodeFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function subObjectCodes(): HasMany
    {
        return $this->hasMany(SubObjectCode::class, 'object_code', 'code');
    }

    public function glCodes(): HasMany
    {
        return $this->hasMany(GlCode::class, 'object_code', 'code');
    }
}
