<?php

namespace App\Models;

use Database\Factories\TdxPublicWifiCircuitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TdxPublicWifiCircuit extends Model
{
    /** @use HasFactory<TdxPublicWifiCircuitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tdx_asset_id',
        'status',
        'location_name',
        'address',
        'speed',
        'po_number',
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
     * @return HasMany<TdxPublicWifiCircuitCost, $this>
     */
    public function costs(): HasMany
    {
        return $this->hasMany(TdxPublicWifiCircuitCost::class);
    }

    /**
     * @return HasOne<TdxPublicWifiCircuitCost, $this>
     */
    public function currentCost(): HasOne
    {
        return $this->costs()->one()->ofMany('fiscal_year', 'max');
    }
}
