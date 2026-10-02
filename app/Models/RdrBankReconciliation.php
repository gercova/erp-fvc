<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RdrBankReconciliation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rdr_bank_reconciliations';

    protected $fillable = [
        'uuid',
        'fund_source_id',
        'bank_account_id',
        'accounting_period_id',
        'period_year',
        'period_month',
        'statement_closing_date',
        'bank_statement_balance',
        'system_calculated_balance',
        'book_calculated_balance',
        'uncredited_deposits',
        'outstanding_checks',
        'unrecorded_bank_charges',
        'unrecorded_bank_credits',
        'reconciled_difference',
        'reconciled_at',
        'status',
        'reconciled_by_user_id',
        'approved_by_user_id',
        'notes',
    ];

    protected $casts = [
        'bank_statement_balance' => 'decimal:2',
        'system_calculated_balance' => 'decimal:2',
        'book_calculated_balance' => 'decimal:2',
        'uncredited_deposits' => 'decimal:2',
        'outstanding_checks' => 'decimal:2',
        'unrecorded_bank_charges' => 'decimal:2',
        'unrecorded_bank_credits' => 'decimal:2',
        'reconciled_difference' => 'decimal:2',
        'statement_closing_date' => 'date',
        'reconciled_at' => 'datetime',
        'period_year' => 'integer',
        'period_month' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (RdrBankReconciliation $rec) {
            if (empty($rec->uuid)) {
                $rec->uuid = (string) Str::uuid();
            }
        });
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class, 'fund_source_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'reconciliation_id');
    }
}
