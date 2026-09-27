<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FundSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fund_sources';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'bank_name',
        'account_number',
        'currency',
        'initial_balance',
        'current_balance',
        'is_cut',
        'is_active',
    ];

    protected $casts = [
        'initial_balance'   => 'decimal:2',
        'current_balance'   => 'decimal:2',
        'is_cut'            => 'boolean',
        'is_active'         => 'boolean',
    ];

    protected static function booted(): void {
        static::creating(function (FundSource $fund) {
            if (empty($fund->uuid)) {
                $fund->uuid = (string) Str::uuid();
            }
        });
    }

    public function transactions(): HasMany {
        return $this->hasMany(ActivityTransaction::class, 'fund_source_id');
    }

    public function cutTransfers(): HasMany {
        return $this->hasMany(RdrCutTransfer::class, 'source_fund_id');
    }

    public function internalLoans(): HasMany {
        return $this->hasMany(RdrInternalLoan::class, 'source_fund_id');
    }

    public function reconciliations(): HasMany {
        return $this->hasMany(RdrBankReconciliation::class, 'fund_source_id');
    }
}
