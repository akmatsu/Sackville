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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ResponsibleDivision, $this>
     */
    public function responsibleDivision(): BelongsTo
    {
        return $this->belongsTo(ResponsibleDivision::class);
    }

    /**
     * @return BelongsTo<ResponsibleLocation, $this>
     */
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
            ResponsibilityScopeType::Department => filled($this->scope_value)
                && $asset->assigned_department_code === $this->scope_value,
            ResponsibilityScopeType::Fund => filled($this->scope_value)
                && $asset->glCode?->fund_code === $this->scope_value,
            ResponsibilityScopeType::Object => filled($this->scope_value)
                && $asset->glCode?->object_code === $this->scope_value,
            ResponsibilityScopeType::SpecificGl => filled($this->scope_value)
                && $asset->glCode?->code_string === $this->scope_value,
        };
    }

    /**
     * Whether this responsibility's scope covers the given public wifi
     * circuit.
     *
     * Kept in sync with {@see TdxPublicWifiCircuit::scopeVisibleTo()}, which
     * applies the same rules at the SQL level for query filtering.
     */
    public function matchesPublicWifiCircuit(TdxPublicWifiCircuit $circuit): bool
    {
        return match ($this->scope_type) {
            ResponsibilityScopeType::Division => $this->responsible_division_id !== null
                && $this->responsible_division_id === $circuit->responsible_division_id,
            ResponsibilityScopeType::Location => $this->responsible_location_id !== null
                && $this->responsible_location_id === $circuit->responsible_location_id,
            ResponsibilityScopeType::Department => filled($this->scope_value)
                && $circuit->assigned_department_code === $this->scope_value,
            ResponsibilityScopeType::Fund => filled($this->scope_value)
                && $circuit->glCode?->fund_code === $this->scope_value,
            ResponsibilityScopeType::Object => filled($this->scope_value)
                && $circuit->glCode?->object_code === $this->scope_value,
            ResponsibilityScopeType::SpecificGl => filled($this->scope_value)
                && $circuit->glCode?->code_string === $this->scope_value,
        };
    }
}
