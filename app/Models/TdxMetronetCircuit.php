<?php

namespace App\Models;

use App\Enums\ResponsibilityScopeType;
use Database\Factories\TdxMetronetCircuitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TdxMetronetCircuit extends Model
{
    /** @use HasFactory<TdxMetronetCircuitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tdx_asset_id',
        'status',
        'location_name',
        'circuit_number',
        'speed',
        'notes',
        'assigned_department_code',
        'responsible_division_id',
        'responsible_location_id',
        'gl_code_id',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'raw_payload' => 'json',
    ];

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
     * @return BelongsTo<GlCode, $this>
     */
    public function glCode(): BelongsTo
    {
        return $this->belongsTo(GlCode::class);
    }

    /**
     * @return HasMany<TdxMetronetCircuitCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(TdxMetronetCircuitCost::class);
    }

    /**
     * @return HasOne<TdxMetronetCircuitCost, $this>
     */
    public function currentCost(): HasOne
    {
        return $this->costs()->one()->ofMany('fiscal_year', 'max');
    }

    /**
     * @return HasMany<MetronetCircuitReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(MetronetCircuitReview::class);
    }

    /**
     * Scope circuits to those the user can act on, based on their
     * responsibility scopes. A verbatim copy of
     * {@see TdxPublicWifiCircuit::scopeVisibleTo()}: this table carries the
     * same `assigned_department_code` / `responsible_division_id` /
     * `responsible_location_id` / `gl_code_id` columns, so the same
     * Fund/Department/Division/Location/Object/SpecificGl matching applies
     * unchanged. Kept in sync with
     * {@see Responsibility::matchesMetronetCircuit()}, which applies the
     * same rules in PHP for a single already-loaded circuit.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
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
                    ResponsibilityScopeType::Division => $responsibility->responsible_division_id !== null
                        ? $query->where('responsible_division_id', $responsibility->responsible_division_id)
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Location => $responsibility->responsible_location_id !== null
                        ? $query->where('responsible_location_id', $responsibility->responsible_location_id)
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Department => filled($responsibility->scope_value)
                        ? $query->where('assigned_department_code', $responsibility->scope_value)
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Fund => filled($responsibility->scope_value)
                        ? $query->whereHas('glCode', fn (Builder $query) => $query->where('fund_code', $responsibility->scope_value))
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Object => filled($responsibility->scope_value)
                        ? $query->whereHas('glCode', fn (Builder $query) => $query->where('object_code', $responsibility->scope_value))
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::SpecificGl => filled($responsibility->scope_value)
                        ? $query->whereHas('glCode', fn (Builder $query) => $query->where('code_string', $responsibility->scope_value))
                        : $query->whereRaw('1 = 0'),
                });
            }
        });
    }

    /**
     * Scope circuits that still need a review decision made about them this
     * cycle: the sync job's own inverse of the condition it uses to mark a
     * circuit `Surplus` once TDX stops reporting it.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeReviewable(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('status')->orWhere('status', '!=', 'Surplus'));
    }
}
