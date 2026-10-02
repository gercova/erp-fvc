<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'uuid',
        'fund_source_id',
        'account_number',
        'cci',
        'bank_name',
        'account_type',
        'currency',
        'accounting_account_id',
        'initial_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BankAccount $account) {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid();
            }
        });
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class, 'fund_source_id');
    }

    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'accounting_account_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(BankMovement::class, 'bank_account_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(RdrBankReconciliation::class, 'bank_account_id');
    }

    public function internalTransfersAsSource(): HasMany
    {
        return $this->hasMany(InternalTransfer::class, 'source_bank_account_id');
    }

    public function internalTransfersAsDestination(): HasMany
    {
        return $this->hasMany(InternalTransfer::class, 'destination_bank_account_id');
    }
}
