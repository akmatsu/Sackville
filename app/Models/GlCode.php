<?php

namespace App\Models;

use App\Support\Gl\GlCodeString;
use Database\Factories\GlCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlCode extends Model
{
    /** @use HasFactory<GlCodeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fund_code',
        'department_code',
        'division_id',
        'object_code',
        'sub_object_code_id',
        'label',
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

    protected static function booted(): void
    {
        static::saving(function (GlCode $glCode): void {
            $glCode->code_string = $glCode->buildCodeString();
        });
    }

    public function buildCodeString(): string
    {
        return GlCodeString::build(
            $this->fund_code,
            $this->department_code,
            $this->division?->code,
            $this->object_code,
            $this->subObjectCode?->code,
        );
    }

    /**
     * @return BelongsTo<Fund, $this>
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'fund_code', 'code');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_code', 'code');
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * @return BelongsTo<ObjectCode, $this>
     */
    public function objectCode(): BelongsTo
    {
        return $this->belongsTo(ObjectCode::class, 'object_code', 'code');
    }

    /**
     * @return BelongsTo<SubObjectCode, $this>
     */
    public function subObjectCode(): BelongsTo
    {
        return $this->belongsTo(SubObjectCode::class);
    }
}
