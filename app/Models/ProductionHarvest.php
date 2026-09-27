<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductionHarvest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_harvests';

    protected $fillable = [
        'uuid',
        'produced_item_id',
        'production_campaign_id',
        'production_batch_id',
        'agricultural_plot_id',
        'agricultural_nursery_id',
        'field_ticket_code',
        'field_weight_kg',
        'harvest_date',
        'quantity',
        'quality_grade',
        'unit_cost_calculated',
        'warehouse_id',
        'stock_product_id',
        'registered_by_user_id',
        'notes',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'quantity' => 'decimal:3',
        'field_weight_kg' => 'decimal:3',
        'unit_cost_calculated' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductionHarvest $harvest) {
            if (empty($harvest->uuid)) {
                $harvest->uuid = (string) Str::uuid();
            }
        });
    }

    public function producedItem(): BelongsTo
    {
        return $this->belongsTo(ProducedItem::class, 'produced_item_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ProductionCampaign::class, 'production_campaign_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function plot(): BelongsTo
    {
        return $this->belongsTo(AgriculturalPlot::class, 'agricultural_plot_id');
    }

    public function nursery(): BelongsTo
    {
        return $this->belongsTo(AgriculturalNursery::class, 'agricultural_nursery_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stockProduct(): BelongsTo
    {
        return $this->belongsTo(StockProduct::class, 'stock_product_id');
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
