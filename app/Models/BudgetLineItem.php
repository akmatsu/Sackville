<?php

namespace App\Models;

use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use App\Enums\ResponsibilityScopeType;
use Database\Factories\BudgetLineItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLineItem extends Model
{
    /** @use HasFactory<BudgetLineItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'budget_cycle_id',
        'responsible_division_id',
        'item_type',
        'tdx_asset_id',
        'hardware_model_id',
        'software_product_id',
        'with_docking',
        'quantity',
        'previous_cost',
        'proposed_cost',
        'description',
        'justification',
        'status',
        'created_by_id',
        'last_modified_by_id',
    ];

    protected $casts = [
        'with_docking' => 'boolean',
        'item_type' => BudgetLineItemType::class,
        'status' => BudgetLineItemStatus::class,
    ];

    /**
     * @return BelongsTo<BudgetCycle, $this>
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BudgetCycle::class, 'budget_cycle_id');
    }

    /**
     * @return BelongsTo<ResponsibleDivision, $this>
     */
    public function responsibleDivision(): BelongsTo
    {
        return $this->belongsTo(ResponsibleDivision::class);
    }

    /**
     * @return BelongsTo<TdxAsset, $this>
     */
    public function tdxAsset(): BelongsTo
    {
        return $this->belongsTo(TdxAsset::class);
    }

    /**
     * @return BelongsTo<HardwareModel, $this>
     */
    public function hardwareModel(): BelongsTo
    {
        return $this->belongsTo(HardwareModel::class);
    }

    /**
     * @return BelongsTo<SoftwareProduct, $this>
     */
    public function softwareProduct(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by_id');
    }

    /**
     * @return HasMany<LineItemGlAllocation, $this>
     */
    public function glAllocations(): HasMany
    {
        return $this->hasMany(LineItemGlAllocation::class);
    }

    /**
     * Scope line items to those the user can see, based on their
     * responsibility scopes. Mirrors {@see TdxAsset::scopeVisibleTo()}:
     * `Division` matches on `responsible_division_id` directly, while
     * `Department`/`Fund`/`Object`/`SpecificGl` match through the line
     * item's GL allocation(s) once one has been assigned. `Location` has no
     * match point on a line item and never matches.
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
                    ResponsibilityScopeType::Location => $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Department => filled($responsibility->scope_value)
                        ? $query->whereHas('glAllocations.glCode', fn (Builder $query) => $query->where('department_code', $responsibility->scope_value))
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Fund => filled($responsibility->scope_value)
                        ? $query->whereHas('glAllocations.glCode', fn (Builder $query) => $query->where('fund_code', $responsibility->scope_value))
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::Object => filled($responsibility->scope_value)
                        ? $query->whereHas('glAllocations.glCode', fn (Builder $query) => $query->where('object_code', $responsibility->scope_value))
                        : $query->whereRaw('1 = 0'),
                    ResponsibilityScopeType::SpecificGl => filled($responsibility->scope_value)
                        ? $query->whereHas('glAllocations.glCode', fn (Builder $query) => $query->where('code_string', $responsibility->scope_value))
                        : $query->whereRaw('1 = 0'),
                });
            }
        });
    }
}
