<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementAuditLog extends Model
{
    use HasFactory;

    protected $table = 'agreement_audit_logs';

    protected $fillable = [
        'agreement_id',
        'user_id',
        'action',
        'previous_status',
        'new_status',
        'reason',
        'ip_address',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function agreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }
}
