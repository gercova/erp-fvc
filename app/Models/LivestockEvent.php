<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LivestockEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'livestock_events';

    protected $fillable = [
        'uuid',
        'livestock_unit_id',
        'event_type',
        'event_date',
        'description',
        'dosage_or_ration',
        'feeding_phase',
        'head_affected_count',
        'production_raw_material_id',
        'input_quantity_used',
        'input_cost',
        'labor_cost',
        'total_cost',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'event_date'            => 'date',
        'head_affected_count'   => 'integer',
        'input_quantity_used'   => 'decimal:3',
        'input_cost'            => 'decimal:2',
        'labor_cost'            => 'decimal:2',
        'total_cost'            => 'decimal:2',
    ];

    protected static function booted(): void {
        static::creating(function (LivestockEvent $event) {
            if (empty($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
            if (empty($event->total_cost)) {
                $event->total_cost = ($event->input_cost ?? 0) + ($event->labor_cost ?? 0);
            }
        });
    }

    public function livestockUnit(): BelongsTo {
        return $this->belongsTo(LivestockUnit::class, 'livestock_unit_id');
    }

    public function rawMaterial(): BelongsTo {
        return $this->belongsTo(ProductionRawMaterial::class, 'production_raw_material_id');
    }

    public function recordedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function getEventTypeBadgeAttribute(): string {
        return match ($this->event_type) {
            'HEALTH_TREATMENT'   => '<span class="badge bg-danger text-white"><i class="fas fa-medkit me-1"></i>Tratamiento Sanitario</span>',
            'VACCINATION'        => '<span class="badge bg-warning text-dark"><i class="fas fa-syringe me-1"></i>Vacunación</span>',
            'FEEDING_LOG'        => '<span class="badge bg-primary text-white"><i class="fas fa-utensils me-1"></i>Alimentación</span>',
            'WEIGHT_CONTROL'     => '<span class="badge bg-info text-white"><i class="fas fa-weight me-1"></i>Control de Peso</span>',
            'BREEDING_SERVICE'   => '<span class="badge bg-purple text-white" style="background:#6f42c1;"><i class="fas fa-venus-mars me-1"></i>Servicio / Celo</span>',
            'BIRTH'              => '<span class="badge bg-success text-white"><i class="fas fa-baby me-1"></i>Parto / Nacimiento</span>',
            'WEANING'            => '<span class="badge bg-secondary text-white"><i class="fas fa-exchange-alt me-1"></i>Destete</span>',
            'MORTALITY_DISPOSAL' => '<span class="badge bg-dark text-white"><i class="fas fa-cross me-1"></i>Baja / Mortalidad</span>',
            'SALE_TRANSFER'      => '<span class="badge bg-success text-white"><i class="fas fa-dollar-sign me-1"></i>Venta / Traslado</span>',
            default              => '<span class="badge bg-secondary">' . e($this->event_type) . '</span>',
        };
    }

    public function getFeedingPhaseLabelAttribute(): ?string {
        return match ($this->feeding_phase) {
            'STARTER'     => 'Inicio / Pre-inicio (Starter)',
            'GROWER'      => 'Crecimiento (Grower)',
            'FINISHER'    => 'Engorde / Acabado (Finisher)',
            'MAINTENANCE' => 'Mantenimiento',
            'LACTATION'   => 'Lactancia / Maternidad',
            default       => $this->feeding_phase,
        };
    }
}
