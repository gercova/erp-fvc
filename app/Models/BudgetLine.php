<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BudgetLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'budget_lines';

    protected $fillable = [
        'uuid',
        'budget_id',
        'line_code',
        'chart_of_account_id',
        'account_code',
        'category_name',
        'productive_activity_id',
        'cost_center_code',
        'fund_source_id',
        'period_month',
        'allocated_amount',
        'modified_amount',
        'current_amount',
        'alert_threshold_percentage',
        'alert_sent_at',
        'notes',
    ];

    protected $casts = [
        'period_month'                  => 'integer',
        'allocated_amount'              => 'decimal:2',
        'modified_amount'               => 'decimal:2',
        'current_amount'                => 'decimal:2',
        'alert_threshold_percentage'    => 'decimal:2',
        'alert_sent_at'                 => 'datetime',
    ];

    protected static function booted(): void {
        static::creating(function (BudgetLine $line) {
            if (empty($line->uuid)) {
                $line->uuid = (string) Str::uuid();
            }
            if (empty($line->current_amount)) {
                $line->current_amount = (float) $line->allocated_amount + (float) $line->modified_amount;
            }
        });
    }

    public function budget(): BelongsTo {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function account(): BelongsTo {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function costCenter(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function fundSource(): BelongsTo {
        return $this->belongsTo(FundSource::class, 'fund_source_id');
    }

    public function outgoingModifications(): HasMany {
        return $this->hasMany(BudgetModification::class, 'source_budget_line_id');
    }

    public function incomingModifications(): HasMany {
        return $this->hasMany(BudgetModification::class, 'destination_budget_line_id');
    }
}
