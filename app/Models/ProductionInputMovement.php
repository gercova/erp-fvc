<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductionInputMovement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_input_movements';

    protected $fillable = [
        'uuid',
        'production_raw_material_id',
        'productive_activity_id',
        'production_campaign_id',
        'production_batch_id',
        'movement_type',
        'movement_date',
        'quantity',
        'unit_cost',
        'total_cost',
        'buy_id',
        'detail_buy_id',
        'registered_by_user_id',
        'notes',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity'      => 'decimal:3',
        'unit_cost'     => 'decimal:2',
        'total_cost'    => 'decimal:2',
    ];

    protected static function booted(): void {
        static::creating(function (ProductionInputMovement $movement) {
            if (empty($movement->uuid)) {
                $movement->uuid = (string) Str::uuid();
            }
            if (empty($movement->total_cost) && $movement->quantity && $movement->unit_cost) {
                $movement->total_cost = round($movement->quantity * $movement->unit_cost, 2);
            }
        });
    }

    public function rawMaterial(): BelongsTo {
        return $this->belongsTo(ProductionRawMaterial::class, 'production_raw_material_id');
    }

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function campaign(): BelongsTo {
        return $this->belongsTo(ProductionCampaign::class, 'production_campaign_id');
    }

    public function batch(): BelongsTo {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function buy(): BelongsTo {
        return $this->belongsTo(Buy::class, 'buy_id');
    }

    public function detailBuy(): BelongsTo {
        return $this->belongsTo(DetailBuy::class, 'detail_buy_id');
    }

    public function registeredByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function getMovementTypeBadgeAttribute(): string {
        return match ($this->movement_type) {
            'inflow_purchase'    => '<span class="badge bg-success text-white">Ingreso / Compra</span>',
            'outflow_consumption'=> '<span class="badge bg-danger text-white">Consumo en Campo</span>',
            'adjustment'         => '<span class="badge bg-info text-white">Ajuste de Stock</span>',
            default              => '<span class="badge bg-light text-dark">' . e($this->movement_type) . '</span>',
        };
    }
}
