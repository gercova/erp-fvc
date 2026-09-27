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
        'period_year',
        'period_month',
        'statement_closing_date',
        'bank_statement_balance',
        'system_calculated_balance',
        'reconciled_difference',
        'status',
        'reconciled_by_user_id',
        'notes',
    ];

    protected $casts = [
        'bank_statement_balance' => 'decimal:2',
        'system_calculated_balance' => 'decimal:2',
        'reconciled_difference' => 'decimal:2',
        'statement_closing_date' => 'date',
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

    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }
}
