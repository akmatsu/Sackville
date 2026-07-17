<?php

namespace App\Models;

use App\Enums\ObjectCodeCategory;
use Database\Factories\ObjectCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjectCode extends Model
{
    /** @use HasFactory<ObjectCodeFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'category',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ObjectCodeCategory::class,
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
