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
        'location',
        'status',
        'observations',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'session_date'   => 'date',
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
