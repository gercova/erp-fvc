<?php

namespace App\Models;

use App\Enums\JournalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntryLine extends Model
{
    use HasFactory;

    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'journal_entry_id',
        'line_number',
        'account_id',
        'debit',
        'credit',
        'debit_usd',
        'credit_usd',
        'glosa',
        'third_party_type',
        'third_party_id',
        'third_party_document',
        'cost_center_id',
        'fund_source_id',
        'bank_account_id',
        'document_reference',
    ];

    protected $casts = [
        'line_number' => 'integer',
        'debit'       => 'decimal:2',
        'credit'      => 'decimal:2',
        'debit_usd'   => 'decimal:4',
        'credit_usd'  => 'decimal:4',
    ];

    public static function booted(): void {
        static::updating(function (JournalEntryLine $line) {
            if ($line->journalEntry && $line->journalEntry->isPosted()) {
                throw new \DomainException('Lines of a posted journal entry are immutable and cannot be updated.');
            }
        });

        static::deleting(function (JournalEntryLine $line) {
            if ($line->journalEntry && $line->journalEntry->isPosted()) {
                throw new \DomainException('Lines of a posted journal entry are immutable and cannot be deleted.');
            }
        });
    }

    public function journalEntry(): BelongsTo {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function costCenter(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'cost_center_id');
    }

    public function fundSource(): BelongsTo {
        return $this->belongsTo(FundSource::class, 'fund_source_id');
    }
}
