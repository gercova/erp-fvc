<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AccountingPeriodClosure extends Model
{
    use HasFactory, SoftDeletes, HasApprovals;

    protected $table = 'accounting_period_closures';

    protected $fillable = [
        'uuid',
        'accounting_period_id',
        'closure_type',
        'status',
        'net_result',
        'total_assets',
        'total_liabilities',
        'total_equity',
        'closing_journal_entry_id',
        'opening_journal_entry_id',
        'closed_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'net_result'         => 'decimal:2',
        'total_assets'       => 'decimal:2',
        'total_liabilities'  => 'decimal:2',
        'total_equity'       => 'decimal:2',
        'approved_at'        => 'datetime',
    ];

    protected static function booted(): void {
        static::creating(function (AccountingPeriodClosure $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function period(): BelongsTo {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function closedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function approvedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function closingJournalEntry(): BelongsTo {
        return $this->belongsTo(JournalEntry::class, 'closing_journal_entry_id');
    }

    public function openingJournalEntry(): BelongsTo {
        return $this->belongsTo(JournalEntry::class, 'opening_journal_entry_id');
    }

    // HasApprovals compatibility
    public function user(): BelongsTo {
        return $this->closedByUser();
    }

    public function area(): BelongsTo {
        return $this->belongsTo(Area::class, 'area_id');
    }
}

