<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RdrInternalLoan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rdr_internal_loans';

    protected $fillable = [
        'uuid',
        'loan_code',
        'productive_activity_id',
        'source_fund_id',
        'beneficiary_user_id',
        'loan_reason',
        'amount_lent',
        'amount_repaid',
        'issue_date',
        'due_date',
        'status',
        'repayment_reference',
        'authorized_by_user_id',
        'notes',
    ];

    protected $casts = [
        'amount_lent'   => 'decimal:2',
        'amount_repaid' => 'decimal:2',
        'issue_date'    => 'date',
        'due_date'      => 'date',
    ];

    protected static function booted(): void {
        static::creating(function (RdrInternalLoan $loan) {
            if (empty($loan->uuid)) {
                $loan->uuid = (string) Str::uuid();
            }
            if (empty($loan->loan_code)) {
                $year = date('Y', strtotime($loan->issue_date ?? now()));
                $count = static::whereYear('issue_date', $year)->count() + 1;
                $loan->loan_code = sprintf('PRES-%s-%04d', $year, $count);
            }
        });
    }

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function sourceFund(): BelongsTo {
        return $this->belongsTo(FundSource::class, 'source_fund_id');
    }

    public function beneficiaryUser(): BelongsTo {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }

    public function authorizedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }

    public function getPendingBalanceAttribute(): float {
        return (float) ($this->amount_lent - $this->amount_repaid);
    }
}
