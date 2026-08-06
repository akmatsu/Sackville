<?php

namespace App\Models;

use Database\Factories\TdxAssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        return $this->belongsTo(HardwareModel::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'assigned_division_id');
    }

    public function glCode(): BelongsTo
    {
        return $this->belongsTo(GlCode::class);
    }
}
