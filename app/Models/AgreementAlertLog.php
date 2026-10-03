<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AgreementAlertLog extends Model
{
    use HasFactory;

    protected $table = 'agreement_alert_logs';

    protected $fillable = [
        'alertable_type',
        'alertable_id',
        'threshold_days',
        'target_date',
        'recipient_user_id',
        'notification_id',
        'alert_type',
        'message',
        'sent_at',
    ];

    protected $casts = [
        'threshold_days' => 'integer',
        'target_date'    => 'date',
        'sent_at'        => 'datetime',
    ];

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
