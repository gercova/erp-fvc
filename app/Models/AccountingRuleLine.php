<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingRuleLine extends Model
{
    use HasFactory;

    protected $table = 'rule_lines';

    protected $fillable = [
        'accounting_rule_id',
        'line_type',
        'account_id',
        'calculation_type',
        'percentage',
        'cost_center_source',
        'description',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function rule(): BelongsTo {
        return $this->belongsTo(AccountingRule::class, 'accounting_rule_id');
    }

    public function account(): BelongsTo {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
