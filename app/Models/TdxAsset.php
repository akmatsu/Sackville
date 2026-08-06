<?php

namespace App\Models;

use App\Enums\ResponsibilityScopeType;
use Database\Factories\TdxAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TdxAsset extends Model
{
    /** @use HasFactory<TdxAssetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tdx_asset_id',
        'status',
        'description',
        'asset_tag',
        'serial',
        'hardware_model_id',
        'assigned_user_upn',
        'assigned_department_code',
        'assigned_division_id',
        'assigned_location_name',
        'gl_code_id',
        'acquired_at',
        'fy_replacement',
        'warranty_ends_at',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'acquired_at' => 'date',
        'warranty_ends_at' => 'date',
        'last_synced_at' => 'datetime',
        'raw_payload' => 'json',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(HardwareModel::class, 'hardware_model_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'assigned_division_id');
    }

    public function glCode(): BelongsTo
    {
        return $this->belongsTo(GlCode::class);
    }

    public function replacementSelections(): HasMany
    {
        return $this->hasMany(HardwareReplacementSelection::class);
    }

    /**
     * Scope assets to those the user can act on, based on their responsibility scopes.
     *
     * Kept in sync with {@see Responsibility::matchesAsset()}, which applies the
     * same rules in PHP for a single already-loaded asset.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $responsibilities = $user->responsibilities;

        if ($responsibilities->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($responsibilities): void {
            foreach ($responsibilities as $responsibility) {
                $query->orWhere(fn (Builder $query): Builder => match ($responsibility->scope_type) {
                    ResponsibilityScopeType::Division => $query->whereHas(
                        'division',
                        fn (Builder $query) => $query->where('code', $responsibility->scope_value)
                    ),
                    ResponsibilityScopeType::Department => $query->where(
                        'assigned_department_code',
                        $responsibility->scope_value
                    ),
                    ResponsibilityScopeType::Fund => $query->whereHas(
                        'glCode',
                        fn (Builder $query) => $query->where('fund_code', $responsibility->scope_value)
                    ),
                    ResponsibilityScopeType::Object => $query->whereHas(
                        'glCode',
                        fn (Builder $query) => $query->where('object_code', $responsibility->scope_value)
                    ),
                    ResponsibilityScopeType::SpecificGl => $query->whereHas(
                        'glCode',
                        fn (Builder $query) => $query->where('code_string', $responsibility->scope_value)
                    ),
                });
            }
        });
    }
}
