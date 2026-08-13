<?php

namespace App\Models;

use Database\Factories\VendorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    /** @use HasFactory<VendorFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_email',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * @return HasMany<SoftwareProduct, $this>
     */
    public function softwareProducts(): HasMany
    {
        return $this->hasMany(SoftwareProduct::class);
    }

    /**
     * @return HasMany<HardwareModel, $this>
     */
    public function hardwareModels(): HasMany
    {
        return $this->hasMany(HardwareModel::class);
    }
}
