<?php

namespace App\Models;

use App\Support\Gl\GlCodeString;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlCode extends Model
{
    /** @use HasFactory<\Database\Factories\GlCodeFactory> */
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

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class, 'fund_code', 'code');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_code', 'code');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function objectCode(): BelongsTo
    {
        return $this->belongsTo(ObjectCode::class, 'object_code', 'code');
    }

    public function subObjectCode(): BelongsTo
    {
        return $this->belongsTo(SubObjectCode::class);
    }
}
