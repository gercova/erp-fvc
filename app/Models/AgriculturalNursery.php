<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgriculturalNursery extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agricultural_nurseries';

    protected $fillable = [
        'uuid',
        'productive_activity_id',
        'production_batch_id',
        'name',
        'location',
        'species_name',
        'stage',
        'initial_quantity',
        'current_quantity',
        'survival_rate',
        'sowing_date',
        'estimated_dispatch_date',
        'in_charge_user_id',
        'notes',
    ];

    protected $casts = [
        'sowing_date' => 'date',
        'estimated_dispatch_date' => 'date',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
        'survival_rate' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (AgriculturalNursery $nursery) {
            if (empty($nursery->uuid)) {
                $nursery->uuid = (string) Str::uuid();
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function inChargeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'in_charge_user_id');
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(ProductionHarvest::class, 'agricultural_nursery_id');
    }

    public function getStageBadgeAttribute(): string
    {
        return match ($this->stage) {
            'GERMINATION'               => '<span class="badge bg-warning text-dark">Germinación / Almácigo</span>',
            'SEEDLING_CONTAINER'        => '<span class="badge bg-info text-white">Plántula en Bolsa/Tubo</span>',
            'HARDENING_ACCLIMATIZATION' => '<span class="badge bg-primary text-white">Rustificación / Aclimatación</span>',
            'READY_FOR_FIELD'           => '<span class="badge bg-success text-white">Listo para Campo Definitivo</span>',
            'DISPATCHED'                => '<span class="badge bg-secondary text-white">Despachado / Vendido</span>',
            default                     => '<span class="badge bg-secondary">' . e($this->stage) . '</span>',
        };
    }
}
