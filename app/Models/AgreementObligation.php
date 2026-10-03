<?php

namespace App\Models;

use App\Enums\ObligationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgreementObligation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agreement_obligations';

    protected $fillable = [
        'uuid',
        'agreement_id',
        'responsible_party',
        'responsible_user_id',
        'clause_reference',
        'title',
        'description',
        'due_date',
        'status',
        'completed_at',
        'verified_by_user_id',
        'evidence_notes',
        'evidence_file_path',
        'alerted_thresholds',
    ];

    protected $casts = [
        'status'             => ObligationStatus::class,
        'due_date'           => 'date',
        'completed_at'       => 'datetime',
        'alerted_thresholds' => 'array',
    ];

    protected static function booted(): void {
        static::creating(function (AgreementObligation $obligation) {
            if (empty($obligation->uuid)) {
                $obligation->uuid = (string) Str::uuid();
            }
            if (empty($obligation->title)) {
                $obligation->title = Str::limit($obligation->description ?? 'Compromiso de convenio', 200, '');
            }
        });
    }

    public function agreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function verifier(): BelongsTo {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function responsibleUser(): BelongsTo {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function scopePending($query) {
        return $query->where('status', ObligationStatus::PENDING->value);
    }

    public function scopeOverdue($query) {
        return $query->where('status', ObligationStatus::OVERDUE->value);
    }

    public function scopeCompleted($query) {
        return $query->where('status', ObligationStatus::COMPLETED->value);
    }

    public function isCompleted(): bool {
        $statusVal = $this->status instanceof ObligationStatus ? $this->status->value : (string)$this->status;
        return in_array($statusVal, ['COMPLETED', 'FULFILLED'], true) || $this->completed_at !== null;
    }

    public function isOverdue(): bool {
        if ($this->isCompleted()) {
            return false;
        }
        $statusVal = $this->status instanceof ObligationStatus ? $this->status->value : (string)$this->status;
        if ($statusVal === 'OVERDUE') {
            return true;
        }
        return $this->due_date && $this->due_date->isPast();
    }

    public function isInProgress(): bool {
        $statusVal = $this->status instanceof ObligationStatus ? $this->status->value : (string)$this->status;
        return $statusVal === 'IN_PROGRESS';
    }

    public function isPending(): bool {
        $statusVal = $this->status instanceof ObligationStatus ? $this->status->value : (string)$this->status;
        return $statusVal === 'PENDING';
    }
}
