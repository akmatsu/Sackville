<?php

namespace App\Models;

use App\Enums\BudgetLineItemStatus;
use App\Enums\BudgetLineItemType;
use Database\Factories\BudgetLineItemFactory;
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

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(BudgetCycle::class, 'budget_cycle_id');
    }

    public function tdxAsset(): BelongsTo
    {
        return $this->belongsTo(TdxAsset::class);
    }

    public function hardwareModel(): BelongsTo
    {
        return $this->belongsTo(HardwareModel::class);
    }

    public function softwareProduct(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by_id');
    }

    public function glAllocations(): HasMany
    {
        return $this->hasMany(LineItemGlAllocation::class);
    }
}
