<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InternalTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'internal_transfers';

    protected $fillable = [
        'uuid',
        'transfer_code',
        'transfer_type',
        'source_type',
        'source_bank_account_id',
        'source_arching_cash_id',
        'destination_type',
        'destination_bank_account_id',
        'destination_fund_source_id',
        'amount',
        'transfer_date',
        'reference_number',
        'concept',
        'journal_entry_id',
        'created_by_user_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (InternalTransfer $transfer) {
            if (empty($transfer->uuid)) {
                $transfer->uuid = (string) Str::uuid();
            }
            if (empty($transfer->transfer_code)) {
                $transfer->transfer_code = 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function sourceBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'source_bank_account_id');
    }

    public function sourceArchingCash(): BelongsTo
    {
        return $this->belongsTo(ArchingCash::class, 'source_arching_cash_id');
    }

    public function destinationBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'destination_bank_account_id');
    }

    public function destinationFundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class, 'destination_fund_source_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
