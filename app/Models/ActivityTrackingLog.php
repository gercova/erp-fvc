<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActivityTrackingLog extends Model
{
    use HasFactory;

    protected $table = 'activity_tracking_logs';

    protected $fillable = [
        'uuid',
        'productive_activity_id',
        'user_id',
        'log_date',
        'progress_percent',
        'status',
        'comment',
    ];

    protected $casts = [
        'progress_percent' => 'decimal:2',
        'log_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (ActivityTrackingLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
