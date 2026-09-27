<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgriculturalPlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agricultural_plots';

    protected $fillable = [
        'uuid',
        'productive_activity_id',
        'code',
        'name',
        'sector_location',
        'area_hectares',
        'topography',
        'soil_type',
        'latitude',
        'longitude',
        'polygon_coordinates',
        'status',
        'notes',
    ];

    protected $casts = [
        'area_hectares' => 'decimal:4',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'polygon_coordinates' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AgriculturalPlot $plot) {
            if (empty($plot->uuid)) {
                $plot->uuid = (string) Str::uuid();
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function plantations(): HasMany
    {
        return $this->hasMany(AgriculturalPlantation::class, 'agricultural_plot_id');
    }

    public function yields(): HasMany
    {
        return $this->hasMany(AgriculturalYieldLog::class, 'agricultural_plot_id');
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(ProductionHarvest::class, 'agricultural_plot_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'IN_PRODUCTION' => '<span class="badge bg-success text-white">En Producción</span>',
            'FALLOW_REST'   => '<span class="badge bg-warning text-dark">En Descanso / Barbecho</span>',
            'PREPARATION'   => '<span class="badge bg-info text-white">En Preparación</span>',
            'ABANDONED'     => '<span class="badge bg-danger text-white">Abandonada</span>',
            default         => '<span class="badge bg-secondary">' . e($this->status) . '</span>',
        };
    }
}
