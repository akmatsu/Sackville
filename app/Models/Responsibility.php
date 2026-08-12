<?php

namespace App\Models;

use App\Enums\ResponsibilityRole;
use App\Enums\ResponsibilityScopeType;
use Database\Factories\ResponsibilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ResponsibilityScopeType $scope_type
 * @property ?string $scope_value
 * @property ResponsibilityRole $role
 */
class Responsibility extends Model
{
    /** @use HasFactory<ResponsibilityFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'scope_type',
        'scope_value',
        'responsible_division_id',
        'responsible_location_id',
        'role',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope_type' => ResponsibilityScopeType::class,
            'role' => ResponsibilityRole::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responsibleDivision(): BelongsTo
    {
        return $this->belongsTo(ResponsibleDivision::class);
    }

    public function responsibleLocation(): BelongsTo
    {
        return $this->belongsTo(ResponsibleLocation::class);
    }

    /**
     * Whether this responsibility's scope covers the given TDX asset.
     *
     * Kept in sync with {@see TdxAsset::scopeVisibleTo()}, which applies the
     * same rules at the SQL level for query filtering.
     */
    public function matchesAsset(TdxAsset $asset): bool
    {
        return match ($this->scope_type) {
            ResponsibilityScopeType::Division => $this->responsible_division_id !== null
                && $this->responsible_division_id === $asset->responsible_division_id,
            ResponsibilityScopeType::Location => $this->responsible_location_id !== null
                && $this->responsible_location_id === $asset->responsible_location_id,
            ResponsibilityScopeType::Department => $asset->assigned_department_code === $this->scope_value,
            ResponsibilityScopeType::Fund => $asset->glCode?->fund_code === $this->scope_value,
            ResponsibilityScopeType::Object => $asset->glCode?->object_code === $this->scope_value,
            ResponsibilityScopeType::SpecificGl => $asset->glCode?->code_string === $this->scope_value,
        };
    }
}
