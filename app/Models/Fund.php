<?php

namespace App\Models;

use App\Enums\FundType;
use Database\Factories\FundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fund extends Model
{
    /** @use HasFactory<FundFactory> */
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
        'fund_type',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fund_type' => FundType::class,
            'active' => 'boolean',
        ];
    }

    public function glCodes(): HasMany
    {
        return $this->hasMany(GlCode::class, 'fund_code', 'code');
    }
}
