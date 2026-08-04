<?php

namespace App\Models;

use Database\Factories\SoftwareLicenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareLicense extends Model
{
    /** @use HasFactory<SoftwareLicenseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'software_product_id',
        'fiscal_year',
        'license_count',
        'unit_cost',
        'total_cost',
        'license_expiration',
        'license_notes',
        'justification',
    ];

    protected $casts = [
        'license_expiration' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(SoftwareProduct::class, 'software_product_id');
    }
}
