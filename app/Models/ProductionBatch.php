<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductionBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_batches';

    protected $fillable = [
        'uuid',
        'production_campaign_id',
        'batch_code',
        'phase_name',
        'phase_type',
        'start_date',
        'end_date',
        'initial_quantity',
        'current_quantity',
        'unit_measure',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'initial_quantity'  => 'decimal:2',
        'current_quantity'  => 'decimal:2',
    ];

    protected static function booted(): void {
        static::creating(function (ProductionBatch $batch) {
            if (empty($batch->uuid)) {
                $batch->uuid = (string) Str::uuid();
            }
        });
    }

    public function campaign(): BelongsTo {
        return $this->belongsTo(ProductionCampaign::class, 'production_campaign_id');
    }

    public function laborCosts(): HasMany {
        return $this->hasMany(ProductionLaborCost::class, 'production_batch_id');
    }

    public function inputMovements(): HasMany {
        return $this->hasMany(ProductionInputMovement::class, 'production_batch_id');
    }

    public function harvests(): HasMany {
        return $this->hasMany(ProductionHarvest::class, 'production_batch_id');
    }

    public function getPhaseTypeLabelAttribute(): string {
        return match ($this->phase_type) {
            'start'       => 'Inicio / Alevinaje',
            'growth'      => 'Crecimiento / Desarrollo',
            'fattening'   => 'Engorde / Acabado',
            'sowing'      => 'Siembra / Plantación',
            'maintenance' => 'Mantenimiento / Labores',
            'harvest'     => 'Cosecha / Recolección',
            default       => ucfirst($this->phase_type ?? 'Otro'),
        };
    }

    public function getStatusBadgeAttribute(): string {
        return match ($this->status) {
            'active'    => '<span class="badge bg-primary text-white">Activo / En curso</span>',
            'completed' => '<span class="badge bg-success text-white">Completado</span>',
            'cancelled' => '<span class="badge bg-danger text-white">Cancelado</span>',
            default     => '<span class="badge bg-light text-dark">' . e($this->status) . '</span>',
        };
    }
}
