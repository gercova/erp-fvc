<?php

namespace App\Models;

use App\Enums\ServiceCategory;
use App\Services\Agreements\AgreementCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TechnologicalService extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'technological_services';

    protected $fillable = [
        'uuid',
        'code',
        'product_id',
        'name',
        'description',
        'area_id',
        'productive_activity_id',
        'category',
        'delivery_modality',
        'unit_price',
        'currency',
        'estimated_hours',
        'requires_deliverable',
        'is_active',
    ];

    protected $casts = [
        'category'             => ServiceCategory::class,
        'unit_price'           => 'decimal:2',
        'estimated_hours'      => 'integer',
        'requires_deliverable' => 'boolean',
        'is_active'            => 'boolean',
    ];

    protected static function booted(): void {
        static::creating(function (TechnologicalService $service) {
            if (empty($service->uuid)) {
                $service->uuid = (string) Str::uuid();
            }
            if (empty($service->code)) {
                $service->code = app(AgreementCodeService::class)->generateServiceCode();
            }
        });
    }

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function area(): BelongsTo {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function productiveActivity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function costCenter(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function engagements(): HasMany {
        return $this->hasMany(ServiceEngagement::class, 'technological_service_id');
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }
}
