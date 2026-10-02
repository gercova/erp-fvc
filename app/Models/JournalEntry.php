<?php

namespace App\Models;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class JournalEntry extends Model
{
    use HasFactory;

    protected $table = 'journal_entries';

    protected $fillable = [
        'uuid',
        'accounting_period_id',
        'entry_number',
        'entry_date',
        'entry_type',
        'concept',
        'currency',
        'exchange_rate',
        'total_debit',
        'total_credit',
        'status',
        'source_type',
        'source_id',
        'event_key',
        'idempotency_key',
        'reversed_by_entry_id',
        'reverses_entry_id',
        'created_by_user_id',
        'posted_by_user_id',
        'posted_at',
    ];

    protected $casts = [
        'entry_date'    => 'date',
        'exchange_rate' => 'decimal:4',
        'total_debit'   => 'decimal:2',
        'total_credit'  => 'decimal:2',
        'status'        => JournalStatus::class,
        'entry_type'    => VoucherType::class,
        'posted_at'     => 'datetime',
    ];

    public static function booted(): void {
        static::creating(function (JournalEntry $entry) {
            if (empty($entry->uuid)) {
                $entry->uuid = (string) Str::uuid();
            }
            if ($entry->status === JournalStatus::POSTED && empty($entry->posted_at)) {
                $entry->posted_at = now();
            }
        });

        // Strict Immutability of Posted Entries
        static::updating(function (JournalEntry $entry) {
            $originalStatus = $entry->getOriginal('status');
            $wasPosted = ($originalStatus instanceof JournalStatus && $originalStatus === JournalStatus::POSTED)
                || $originalStatus === JournalStatus::POSTED->value
                || $originalStatus === 'POSTED';

            if ($wasPosted) {
                // Allow only updating status to REVERSED and reversed_by_entry_id during reversal operations
                $dirtyFields = array_keys($entry->getDirty());
                $allowedReversalFields = ['reversed_by_entry_id', 'status', 'updated_at'];
                $disallowedFields = array_diff($dirtyFields, $allowedReversalFields);

                if (!empty($disallowedFields)) {
                    throw new \DomainException('Posted journal entries are immutable and cannot be updated. Use a reversal entry instead.');
                }
            }
        });

        static::deleting(function (JournalEntry $entry) {
            $isPosted = ($entry->status instanceof JournalStatus && $entry->status === JournalStatus::POSTED)
                || $entry->status === JournalStatus::POSTED->value
                || $entry->status === 'POSTED';

            if ($isPosted) {
                throw new \DomainException('Posted journal entries are immutable and cannot be deleted.');
            }
        });
    }

    public function period(): BelongsTo {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function lines(): HasMany {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id')->orderBy('line_number');
    }

    public function source(): MorphTo {
        return $this->morphTo();
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function poster(): BelongsTo {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    public function reversedBy(): BelongsTo {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_entry_id');
    }

    public function reverses(): BelongsTo {
        return $this->belongsTo(JournalEntry::class, 'reverses_entry_id');
    }

    public function scopePosted(Builder $query): Builder {
        return $query->where('status', JournalStatus::POSTED->value);
    }

    public function scopeForPeriod(Builder $query, int $periodId): Builder {
        return $query->where('accounting_period_id', $periodId);
    }

    public function isPosted(): bool {
        return $this->status === JournalStatus::POSTED;
    }

    public function isReversed(): bool {
        return $this->status === JournalStatus::REVERSED;
    }
}
