<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActivityTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'activity_transactions';

    protected $fillable = [
        'uuid',
        'transaction_code',
        'productive_activity_id',
        'productive_unit_id',
        'category_id',
        'fund_source_id',
        'transaction_type',
        'amount',
        'transaction_date',
        'period_month',
        'period_year',
        'voucher_type',
        'voucher_number',
        'concept',
        'beneficiary_or_payer',
        'cash_id',
        'buy_id',
        'sale_note_id',
        'billing_id',
        'status',
        'registered_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'period_month' => 'integer',
        'period_year' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ActivityTransaction $trx) {
            if (empty($trx->uuid)) {
                $trx->uuid = (string) Str::uuid();
            }
            if (empty($trx->transaction_code)) {
                $year = $trx->period_year ?? date('Y');
                $count = static::where('period_year', $year)->count() + 1;
                $prefix = $trx->transaction_type === 'INCOME' ? 'ING' : 'EGR';
                $trx->transaction_code = sprintf('%s-%s-%05d', $prefix, $year, $count);
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function productiveUnit(): BelongsTo
    {
        return $this->belongsTo(ProductiveUnit::class, 'productive_unit_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityTransactionCategory::class, 'category_id');
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class, 'fund_source_id');
    }

    public function cash(): BelongsTo
    {
        return $this->belongsTo(Cash::class, 'cash_id');
    }

    public function buy(): BelongsTo
    {
        return $this->belongsTo(Buy::class, 'buy_id');
    }

    public function saleNote(): BelongsTo
    {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
