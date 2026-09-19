<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentApproval extends Model
{
    protected $fillable = [
        'document_type',
        'document_id',
        'step_order',
        'role_name',
        'label',
        'approver_id',
        'approver_name',
        'approver_cargo',
        'status',
        'observations',
        'signature_token',
        'signature_data',
        'signed_at',
        'ip_address',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'step_order' => 'integer',
    ];

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDIENTE';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APROBADO';
    }

    public function isObserved(): bool
    {
        return $this->status === 'OBSERVADO';
    }

    public function isRejected(): bool
    {
        return $this->status === 'RECHAZADO';
    }
}
