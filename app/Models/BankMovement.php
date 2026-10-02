<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BankMovement extends Model
{
    use HasFactory;

    protected $table = 'bank_movements';

    protected $fillable = [
        'uuid',
        'bank_account_id',
        'movement_date',
        'operation_number',
        'movement_type',
        'amount',
        'concept',
        'reconciliation_status',
        'matched_journal_entry_id',
        'journal_entry_id',
        'import_batch_id',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (BankMovement $movement) {
            if (empty($movement->uuid)) {
                $movement->uuid = (string) Str::uuid();
            }
        });
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function matchedJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'matched_journal_entry_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'bank_movement_id');
    }
}
