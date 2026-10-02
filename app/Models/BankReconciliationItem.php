<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    use HasFactory;

    protected $table = 'bank_reconciliation_items';

    protected $fillable = [
        'reconciliation_id',
        'bank_movement_id',
        'journal_entry_id',
        'item_type',
        'amount',
        'reference',
        'concept',
        'difference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(RdrBankReconciliation::class, 'reconciliation_id');
    }

    public function bankMovement(): BelongsTo
    {
        return $this->belongsTo(BankMovement::class, 'bank_movement_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
