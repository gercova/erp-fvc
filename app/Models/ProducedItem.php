<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProducedItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produced_items';

    protected $fillable = [
        'uuid',
        'productive_activity_id',
        'name',
        'unit_of_measurement',
        'standard_cost',
        'product_id',
        'is_published_to_sales',
        'description',
    ];

    protected $casts = [
        'standard_cost' => 'decimal:2',
        'is_published_to_sales' => 'boolean',
    ];

    protected static function booted(): void {
        static::creating(function (ProducedItem $item) {
            if (empty($item->uuid)) {
                $item->uuid = (string) Str::uuid();
            }
        });
    }

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function harvests(): HasMany {
        return $this->hasMany(ProductionHarvest::class, 'produced_item_id');
    }

    public function orders(): HasMany {
        return $this->hasMany(ActivityOrder::class, 'produced_item_id');
    }

    public function salesAttributions(): HasMany {
        return $this->hasMany(ActivitySalesAttribution::class, 'produced_item_id');
    }

    public function getPublishedBadgeAttribute(): string {
        if ($this->is_published_to_sales && $this->product_id) {
            return '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>En Catálogo POS</span>';
        }
        return '<span class="badge bg-secondary text-white">No Publicado</span>';
    }
}
