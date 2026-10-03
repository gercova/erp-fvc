<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ServiceAttendee extends Model
{
    use HasFactory;

    protected $table = 'service_attendees';

    protected $fillable = [
        'uuid',
        'service_session_id',
        'service_engagement_id',
        'full_name',
        'dni_or_document',
        'email',
        'phone',
        'organization',
        'attended',
        'evaluation_score',
        'certificate_code',
        'notes',
    ];

    protected $casts = [
        'attended'         => 'boolean',
        'evaluation_score' => 'decimal:2',
    ];

    protected static function booted(): void {
        static::creating(function (ServiceAttendee $attendee) {
            if (empty($attendee->uuid)) {
                $attendee->uuid = (string) Str::uuid();
            }
            if (empty($attendee->service_engagement_id) && !empty($attendee->service_session_id)) {
                $session = ServiceSession::find($attendee->service_session_id);
                if ($session) {
                    $attendee->service_engagement_id = $session->service_engagement_id;
                }
            }
        });
    }

    public function session(): BelongsTo {
        return $this->belongsTo(ServiceSession::class, 'service_session_id');
    }

    public function engagement(): BelongsTo {
        return $this->belongsTo(ServiceEngagement::class, 'service_engagement_id');
    }
}
