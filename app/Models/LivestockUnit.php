<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LivestockUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'livestock_units';

    protected $fillable = [
        'uuid',
        'productive_activity_id',
        'productive_unit_id',
        'species',
        'breed',
        'tracking_type',
        'identifier_code',
        'batch_head_count',
        'sex',
        'birth_or_entry_date',
        'entry_weight_kg',
        'current_weight_kg',
        'mother_identifier',
        'father_identifier',
        'status',
        'notes',
    ];

    protected $casts = [
        'birth_or_entry_date' => 'date',
        'batch_head_count' => 'integer',
        'entry_weight_kg' => 'decimal:2',
        'current_weight_kg' => 'decimal:2',
    ];

    protected static function booted(): void {
        static::creating(function (LivestockUnit $unit) {
            if (empty($unit->uuid)) {
                $unit->uuid = (string) Str::uuid();
            }
        });
    }

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function productiveUnit(): BelongsTo {
        return $this->belongsTo(ProductiveUnit::class, 'productive_unit_id');
    }

    public function events(): HasMany {
        return $this->hasMany(LivestockEvent::class, 'livestock_unit_id')->orderBy('event_date', 'desc');
    }

    public function getSpeciesLabelAttribute(): string {
        return match ($this->species) {
            'CATTLE'     => 'Bovino / Vacuno',
            'PIG'        => 'Porcino / Cerdo',
            'GUINEA_PIG' => 'Cuyícola / Cuy',
            'POULTRY'    => 'Avícola / Aves',
            'FISH_POND'  => 'Piscícola / Peces',
            'SHEEP_GOAT' => 'Ovino / Caprino',
            default      => e($this->species),
        };
    }

    public function getStatusBadgeAttribute(): string {
        return match ($this->status) {
            'ACTIVE'       => '<span class="badge bg-success text-white">Activo / En Producción</span>',
            'GESTATION'    => '<span class="badge bg-info text-white">Gestación</span>',
            'LACTATION'    => '<span class="badge bg-primary text-white">Lactancia</span>',
            'FATTENING'    => '<span class="badge bg-warning text-dark">Engorde / Finalización</span>',
            'QUARANTINE'   => '<span class="badge bg-danger text-white">Cuarentena</span>',
            'SOLD'         => '<span class="badge bg-secondary text-white">Vendido</span>',
            'SLAUGHTERED'  => '<span class="badge bg-dark text-white">Beneficiado</span>',
            'DECEASED'     => '<span class="badge bg-danger text-white">Baja / Mortalidad</span>',
            'TRANSFERRED'  => '<span class="badge bg-info text-dark">Transferido</span>',
            default        => '<span class="badge bg-secondary">' . e($this->status) . '</span>',
        };
    }
}
