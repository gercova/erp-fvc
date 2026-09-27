<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductiveActivity extends Model
{
    use HasFactory, SoftDeletes, HasApprovals;

    protected $table = 'productive_activities';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'type',
        'cost_center_code',
        'area_id',
        'head_user_id',
        'default_fund_source_id',
        'status',
        'execution_progress_percent',
        'monitoring_observations',
        'description',
        'order_index',
    ];

    protected $casts = [
        'execution_progress_percent' => 'decimal:2',
        'order_index' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductiveActivity $activity) {
            if (empty($activity->uuid)) {
                $activity->uuid = (string) Str::uuid();
            }
        });
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    // Proxy for HasApprovals
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function defaultFundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class, 'default_fund_source_id');
    }

    public function productiveUnits(): HasMany
    {
        return $this->hasMany(ProductiveUnit::class, 'productive_activity_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ActivityTransaction::class, 'productive_activity_id');
    }

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(ActivityTrackingLog::class, 'productive_activity_id')->orderByDesc('log_date');
    }

    public function periodClosures(): HasMany
    {
        return $this->hasMany(ActivityPeriodClosure::class, 'productive_activity_id');
    }

    public function cutTransfers(): HasMany
    {
        return $this->hasMany(RdrCutTransfer::class, 'productive_activity_id');
    }

    public function internalLoans(): HasMany
    {
        return $this->hasMany(RdrInternalLoan::class, 'productive_activity_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(ProductionCampaign::class, 'productive_activity_id');
    }

    public function inputMovements(): HasMany
    {
        return $this->hasMany(ProductionInputMovement::class, 'productive_activity_id');
    }

    public function producedItems(): HasMany
    {
        return $this->hasMany(ProducedItem::class, 'productive_activity_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ActivityOrder::class, 'productive_activity_id');
    }

    public function salesAttributions(): HasMany
    {
        return $this->hasMany(ActivitySalesAttribution::class, 'productive_activity_id');
    }

    public function plots(): HasMany
    {
        return $this->hasMany(AgriculturalPlot::class, 'productive_activity_id');
    }

    public function nurseries(): HasMany
    {
        return $this->hasMany(AgriculturalNursery::class, 'productive_activity_id');
    }

    public function livestockUnits(): HasMany
    {
        return $this->hasMany(LivestockUnit::class, 'productive_activity_id');
    }

    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'activity_collaborators', 'productive_activity_id', 'user_id')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }
}
