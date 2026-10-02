<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    use HasFactory;

    protected $table = 'accounting_periods';

    protected $fillable = [
        'fiscal_year',
        'month',
        'period_code',
        'start_date',
        'end_date',
        'status',
        'closed_by_user_id',
        'closed_at',
        'reopened_by_user_id',
        'reopened_at',
        'notes',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'month'       => 'integer',
        'start_date'  => 'date',
        'end_date'    => 'date',
        'closed_at'   => 'datetime',
        'reopened_at' => 'datetime',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_user_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'accounting_period_id');
    }

    public function scopeOpen(Builder $query): Builder {
        return $query->where('status', 'OPEN');
    }

    public function scopeForYear(Builder $query, int $year): Builder {
        return $query->where('fiscal_year', $year);
    }

    public function isOpen(): bool {
        return $this->status === 'OPEN';
    }

    public function isSoftClosed(): bool {
        return $this->status === 'SOFT_CLOSED';
    }

    public function isLocked(): bool {
        return $this->status === 'LOCKED';
    }
}
