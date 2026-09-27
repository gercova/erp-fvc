<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgriculturalPlantation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agricultural_plantations';

    protected $fillable = [
        'uuid',
        'agricultural_plot_id',
        'crop_species',
        'crop_type',
        'variety',
        'planting_date',
        'estimated_harvest_date',
        'harvest_frequency_days',
        'plant_count',
        'spacing_meters',
        'expected_yield_per_ha',
        'age_years',
        'status',
        'notes',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'estimated_harvest_date' => 'date',
        'harvest_frequency_days' => 'integer',
        'plant_count' => 'integer',
        'expected_yield_per_ha' => 'decimal:2',
        'age_years' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (AgriculturalPlantation $plantation) {
            if (empty($plantation->uuid)) {
                $plantation->uuid = (string) Str::uuid();
            }
        });
    }

    public function plot(): BelongsTo
    {
        return $this->belongsTo(AgriculturalPlot::class, 'agricultural_plot_id');
    }

    public function yieldLogs(): HasMany
    {
        return $this->hasMany(AgriculturalYieldLog::class, 'agricultural_plantation_id');
    }

    public function getCropTypeBadgeAttribute(): string
    {
        return $this->crop_type === 'PERMANENT'
            ? '<span class="badge bg-primary text-white"><i class="fas fa-tree me-1"></i>Permanente (Recurrente)</span>'
            : '<span class="badge bg-info text-dark"><i class="fas fa-seedling me-1"></i>Ciclo Corto</span>';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'VEGETATIVE_DEVELOPMENT' => '<span class="badge bg-warning text-dark">Desarrollo Vegetativo</span>',
            'FULL_PRODUCTION'       => '<span class="badge bg-success text-white">En Plena Producción</span>',
            'DECLINING'             => '<span class="badge bg-secondary text-white">En Declinación</span>',
            'RENOVATION'            => '<span class="badge bg-info text-white">En Renovación</span>',
            'ERADICATED'             => '<span class="badge bg-danger text-white">Erradicado</span>',
            default                  => '<span class="badge bg-secondary">' . e($this->status) . '</span>',
        };
    }
}
