<?php

namespace App\Models;

use Database\Factories\TdxMobilePlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TdxMobilePlan extends Model
{
    /** @use HasFactory<TdxMobilePlanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tdx_asset_id',
        'status',
        'carrier',
        'po_number',
        'plan_status',
        'plan_description',
        'description',
        'asset_tag',
        'serial',
        'assigned_user_upn',
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

    /**
     * Mobile devices synced under this plan, matched on TDX's ParentSerial
     * against this plan's serial — see TdxAsset::plan() for the inverse and
     * its caveats (no DB-level referential integrity, relies on `serial`
     * being effectively unique).
     */
    public function devices(): HasMany
    {
        return $this->hasMany(TdxAsset::class, 'plan_serial', 'serial');
    }
}
