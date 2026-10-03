<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServiceHourLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_hour_logs';

    protected $fillable = [
        'uuid',
        'service_engagement_id',
        'specialist_user_id',
        'log_date',
        'hours',
        'modality',
        'activity_performed',
        'location_or_farm',
        'client_contact_name',
        'client_signed',
        'observations',
        'is_authorized_overage',
    ];

    protected $casts = [
        'log_date'              => 'date',
        'hours'                 => 'decimal:2',
        'client_signed'         => 'boolean',
        'is_authorized_overage' => 'boolean',
    ];

    protected static function booted(): void {
        static::creating(function (ServiceHourLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });

        static::saved(function (ServiceHourLog $log) {
            $log->engagement?->recalculateConsumedHours();
        });

        static::deleted(function (ServiceHourLog $log) {
            $log->engagement?->recalculateConsumedHours();
        });
    }

    public function engagement(): BelongsTo {
        return $this->belongsTo(ServiceEngagement::class, 'service_engagement_id');
    }

    public function specialist(): BelongsTo {
        return $this->belongsTo(User::class, 'specialist_user_id');
    }
}
