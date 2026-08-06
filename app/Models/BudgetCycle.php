<?php

namespace App\Models;

use App\Enums\BudgetCycleStatus;
use Database\Factories\BudgetCycleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCycle extends Model
{
    /** @use HasFactory<BudgetCycleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fiscal_year',
        'opens_at',
        'closes_at',
        'status',
    ];

    protected $casts = [
        'opens_at' => 'date',
        'closes_at' => 'date',
        'status' => BudgetCycleStatus::class,
    ];

    public function lineItems(): HasMany
    {
        return $this->hasMany(BudgetLineItem::class);
    }

    public function replacementSelections(): HasMany
    {
        return $this->hasMany(HardwareReplacementSelection::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', BudgetCycleStatus::Open);
    }
}
