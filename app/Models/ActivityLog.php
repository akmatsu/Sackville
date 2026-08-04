<?php

namespace App\Models;

use App\Enums\ActivityLogAction;
use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    protected $table = 'activity_log';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'diff',
        'actor_id',
        'at',
    ];

    protected $casts = [
        'diff' => 'json',
        'at' => 'datetime',
        'action' => ActivityLogAction::class,
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
