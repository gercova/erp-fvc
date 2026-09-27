<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductionCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_campaigns';

    protected $fillable = [
        'uuid',
        'productive_activity_id',
        'campaign_code',
        'name',
        'start_date',
        'end_date',
        'production_targets',
        'target_quantity',
        'target_unit',
        'budget_allocated',
        'status',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_quantity' => 'decimal:2',
        'budget_allocated' => 'decimal:2',
    ];

    protected static function booted(): void {
        static::creating(function (ProductionCampaign $campaign) {
            if (empty($campaign->uuid)) {
                $campaign->uuid = (string) Str::uuid();
            }

            if (empty($campaign->campaign_code)) {
                $year = $campaign->start_date ? Carbon::parse($campaign->start_date)->format('Y') : date('Y');
                $count = static::whereYear('start_date', $year)->count() + 1;
                $campaign->campaign_code = sprintf('CAMP-%s-%03d', $year, $count);
            }
        });
    }

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function batches(): HasMany {
        return $this->hasMany(ProductionBatch::class, 'production_campaign_id');
    }

    public function inputMovements(): HasMany {
        return $this->hasMany(ProductionInputMovement::class, 'production_campaign_id');
    }

    public function harvests(): HasMany {
        return $this->hasMany(ProductionHarvest::class, 'production_campaign_id');
    }

    public function salesAttributions(): HasMany {
        return $this->hasMany(ActivitySalesAttribution::class, 'production_campaign_id');
    }

    public function getStatusBadgeAttribute(): string {
        return match ($this->status) {
            'planned'     => '<span class="badge bg-secondary text-white">Planificada</span>',
            'in_progress' => '<span class="badge bg-primary text-white">En Ejecución</span>',
            'closed'      => '<span class="badge bg-success text-white">Cerrada</span>',
            default       => '<span class="badge bg-light text-dark">' . e($this->status) . '</span>',
        };
    }
}
