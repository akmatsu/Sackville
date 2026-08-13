<?php

namespace App\Models;

use App\Enums\ResponsibilityScopeType;
use App\Enums\TdxAssetSource;
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
        'source',
        'status',
        'product_type',
        'description',
        'asset_tag',
        'serial',
        'plan_serial',
        'hardware_model_id',
        'has_docking_station',
        'assigned_user_upn',
        'assigned_department_code',
        'responsible_division_id',
        'responsible_location_id',
        'gl_code_id',
        'acquired_at',
        'fy_replacement',
        'warranty_ends_at',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'source' => TdxAssetSource::class,
        'has_docking_station' => 'boolean',
        'acquired_at' => 'date',
        'warranty_ends_at' => 'date',
        'last_synced_at' => 'datetime',
        'raw_payload' => 'json',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(HardwareModel::class, 'hardware_model_id');
    }

    /**
     * The mobile plan this device is on, matched on TDX's ParentSerial
     * against the plan's serial rather than a primary key — resolved at
     * query time so it isn't sensitive to which row (plan or device) synced
     * first within a run. No DB-level referential integrity: a plan can be
     * deleted or resynced away and `plan_serial` is left dangling, and the
     * match is only unambiguous because `serial` is expected (not enforced)
     * to be unique on tdx_mobile_plans.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TdxMobilePlan::class, 'plan_serial', 'serial');
    }

    public function responsibleDivision(): BelongsTo
    {
        return $this->belongsTo(ResponsibleDivision::class);
    }

    public function responsibleLocation(): BelongsTo
    {
        return $this->belongsTo(ResponsibleLocation::class);
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
     * Scope assets eligible for replacement selection in $cycle: assets in the
     * given hardware category whose fy_replacement has arrived or passed, that
     * haven't already had a real replacement model picked in an earlier cycle
     * (an opt-out doesn't count, since deferring isn't the same as replaced).
     */
    public function scopeEligibleForReplacement(Builder $query, string $categoryName, BudgetCycle $cycle): Builder
    {
        return $query
            ->whereHas('model.category', fn (Builder $query) => $query->where('name', $categoryName))
            ->where('fy_replacement', '<=', $cycle->fiscal_year)
            ->whereDoesntHave(
                'replacementSelections',
                fn (Builder $query) => $query->where('budget_cycle_id', '!=', $cycle->id)->whereNotNull('hardware_model_id')
            );
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
                    ResponsibilityScopeType::Division => $responsibility->responsible_division_id !== null
                        ? $query->where('responsible_division_id', $responsibility->responsible_division_id)
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Location => $responsibility->responsible_location_id !== null
                        ? $query->where('responsible_location_id', $responsibility->responsible_location_id)
                        : $query->whereRaw('1 = 0'),
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
