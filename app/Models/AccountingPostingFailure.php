<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingPostingFailure extends Model
{
    use HasFactory;

    protected $table = 'accounting_posting_failures';

    protected $fillable = [
        'event_name',
        'source_type',
        'source_id',
        'event_key',
        'payload',
        'error_message',
        'stack_trace',
        'attempts',
        'status',
        'last_attempt_at',
        'reprocessed_at',
    ];

    protected $casts = [
        'payload'         => 'array',
        'attempts'        => 'integer',
        'last_attempt_at' => 'datetime',
        'reprocessed_at'  => 'datetime',
    ];

    public function source(): MorphTo {
        return $this->morphTo();
    }

    public function isFailed(): bool {
        return $this->status === 'FAILED';
    }

    public function isReprocessed(): bool {
        return $this->status === 'REPROCESSED';
    }

    public function markAsReprocessed(): void {
        $this->update([
            'status'         => 'REPROCESSED',
            'reprocessed_at' => now(),
        ]);
    }
}
