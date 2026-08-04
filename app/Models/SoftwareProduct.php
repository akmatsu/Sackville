<?php

namespace App\Models;

use Database\Factories\SoftwareProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareProduct extends Model
{
    /** @use HasFactory<SoftwareProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'default_license_type',
        'billing_frequency',
        'url',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(SoftwareLicense::class);
    }
}
