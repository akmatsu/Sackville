<?php

namespace App\Models;

use Database\Factories\LineItemGlAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineItemGlAllocation extends Model
{
    /** @use HasFactory<LineItemGlAllocationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'budget_line_item_id',
        'gl_code_id',
        'percent',
        'amount',
    ];

    public function lineItem(): BelongsTo
    {
        return $this->belongsTo(BudgetLineItem::class);
    }

    public function glCode(): BelongsTo
    {
        return $this->belongsTo(GlCode::class);
    }
}
