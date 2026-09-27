<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActivityPeriodClosure extends Model
{
    use HasFactory, SoftDeletes, HasApprovals;

    protected $table = 'activity_period_closures';

    protected $fillable = [
        'uuid',
        'closure_code',
        'productive_activity_id',
        'period_year',
        'period_month',
        'total_income',
        'total_expense',
        'net_balance',
        'bn_balance',
        'coop_balance',
        'cash_balance',
        'approval_status',
        'closed_by_user_id',
        'notes',
    ];

    protected $casts = [
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'net_balance' => 'decimal:2',
        'bn_balance' => 'decimal:2',
        'coop_balance' => 'decimal:2',
        'cash_balance' => 'decimal:2',
        'period_year' => 'integer',
        'period_month' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ActivityPeriodClosure $closure) {
            if (empty($closure->uuid)) {
                $closure->uuid = (string) Str::uuid();
            }
            if (empty($closure->closure_code)) {
                $closure->closure_code = sprintf('CIERRE-%d-%02d-%d', $closure->productive_activity_id, $closure->period_month, $closure->period_year);
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    // Proxy for HasApprovals
    public function user(): BelongsTo
    {
        return $this->closedByUser();
    }

    public function area(): BelongsTo
    {
        return $this->activity->area();
    }
}
