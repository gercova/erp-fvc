<?php

namespace App\Models;

use App\Enums\ServiceSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServiceSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_sessions';

    protected $fillable = [
        'uuid',
        'service_engagement_id',
        'session_number',
        'topic',
        'instructor_user_id',
        'session_date',
        'start_time',
        'end_time',
        'duration_hours',
        'location',
        'status',
        'observations',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'session_date'   => 'date',
        'duration_hours' => 'decimal:2',
        'status'         => ServiceSessionStatus::class,
    ];

    protected static function booted(): void {
        static::creating(function (ServiceSession $session) {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
            if (empty($session->session_number)) {
                $max = static::where('service_engagement_id', $session->service_engagement_id)->max('session_number');
                $session->session_number = ($max ?? 0) + 1;
            }
            if (empty($session->duration_hours) && !empty($session->start_time) && !empty($session->end_time)) {
                try {
                    $start = Carbon::parse($session->start_time);
                    $end = Carbon::parse($session->end_time);
                    $session->duration_hours = round(max(0, $end->diffInMinutes($start) / 60), 2);
                } catch (\Throwable $e) {
                    // Ignore parse error
                }
            }
        });

        static::saved(function (ServiceSession $session) {
            $session->engagement?->recalculateConsumedHours();
        });

        static::deleted(function (ServiceSession $session) {
            $session->engagement?->recalculateConsumedHours();
        });
    }

    public function engagement(): BelongsTo {
        return $this->belongsTo(ServiceEngagement::class, 'service_engagement_id');
    }

    public function instructor(): BelongsTo {
        return $this->belongsTo(User::class, 'instructor_user_id');
    }

    public function attendees(): HasMany {
        return $this->hasMany(ServiceAttendee::class, 'service_session_id');
    }
}
