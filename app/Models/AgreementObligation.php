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
        'clause_reference',
        'title',
        'description',
        'due_date',
        'status',
        'completed_at',
        'verified_by_user_id',
        'evidence_notes',
    ];

    protected $casts = [
        'status'       => ObligationStatus::class,
        'due_date'     => 'date',
        'completed_at' => 'datetime',
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

    public function scopePending($query) {
        return $query->where('status', ObligationStatus::PENDING->value);
    }

    public function scopeOverdue($query) {
        return $query->where('status', ObligationStatus::OVERDUE->value);
    }

    public function scopeCompleted($query) {
        return $query->where('status', ObligationStatus::COMPLETED->value);
    }
}
