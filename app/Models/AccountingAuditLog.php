<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'accounting_audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'entry_number',
        'period_code',
        'ip_address',
        'user_agent',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function auditable(): MorphTo {
        return $this->morphTo();
    }
}
