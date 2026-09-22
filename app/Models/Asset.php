<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'area_id',
        'orden',
        'codigo_producto',
        'codigo',
        'descripcion',
        'marca',
        'modelo',
        'serie',
        'costo',
        'condicion',
        'tipo_adquisicion',
        'fecha_adquisicion',
        'anio_adquisicion',
        'ubicacion',
        'custodio',
        'observaciones',
        'estado_operativo',
        'foto_path',
        'is_reconciled',
        'reconciled_at',
        'reconciled_by',
        'user_id',
    ];

    protected $casts = [
        'orden'             => 'integer',
        'costo'             => 'decimal:2',
        'fecha_adquisicion' => 'date',
        'is_reconciled'     => 'boolean',
        'reconciled_at'     => 'datetime',
    ];

    protected $attributes = [
        'marca'            => 'SIN MARCA',
        'modelo'           => 'SIN MODELO',
        'serie'            => 'SIN SERIE',
        'costo'            => 0.00,
        'condicion'        => 'B',
        'tipo_adquisicion' => 'C',
        'is_reconciled'    => false,
        'estado_operativo' => 'OPERATIVO',
    ];

    protected static function booted(): void
    {
        static::creating(function (Asset $asset) {
            if (empty($asset->uuid)) {
                $asset->uuid = (string) Str::uuid();
            }

            // Si orden no está asignado o es 0, calcular el siguiente secuencial para el área
            if (empty($asset->orden) || $asset->orden == 0) {
                $maxOrden = static::where('area_id', $asset->area_id)->max('orden') ?? 0;
                $asset->orden = $maxOrden + 1;
            }

            // Normalizar valores por defecto solicitados
            if (empty($asset->marca)) {
                $asset->marca = 'SIN MARCA';
            }
            if (empty($asset->modelo)) {
                $asset->modelo = 'SIN MODELO';
            }
            if (empty($asset->serie)) {
                $asset->serie = 'SIN SERIE';
            }
            if (!isset($asset->costo) || $asset->costo === null) {
                $asset->costo = 0.00;
            }

            // Mapeo flexible de condición (G -> B, F -> R, P -> M, W -> BAJA)
            $condicionMap = [
                'G' => 'B', 'B' => 'B',
                'F' => 'R', 'R' => 'R',
                'P' => 'M', 'M' => 'M',
                'W' => 'BAJA', 'BAJA' => 'BAJA',
            ];
            $asset->condicion = $condicionMap[strtoupper($asset->condicion ?? 'B')] ?? 'B';

            // Mapeo flexible de tipo de adquisición (P -> C, C -> C, D -> D)
            $adqMap = [
                'P' => 'C', 'C' => 'C',
                'D' => 'D',
            ];
            $asset->tipo_adquisicion = $adqMap[strtoupper($asset->tipo_adquisicion ?? 'C')] ?? 'C';

            // Sincronizar año de adquisición si fecha está presente
            if (!empty($asset->fecha_adquisicion) && empty($asset->anio_adquisicion)) {
                $asset->anio_adquisicion = $asset->fecha_adquisicion->format('Y');
            }
        });
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function getFormattedOrdenAttribute(): string
    {
        return str_pad((string) $this->orden, 2, '0', STR_PAD_LEFT);
    }

    public function getCondicionLabelAttribute(): string
    {
        return match ($this->condicion) {
            'B'     => 'Bueno',
            'R'     => 'Regular',
            'M'     => 'Malo',
            'BAJA'  => 'Baja',
            default => $this->condicion,
        };
    }

    public function getCondicionBadgeAttribute(): string
    {
        return match ($this->condicion) {
            'B'     => '<span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check-circle me-1"></i>Bueno (B)</span>',
            'R'     => '<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-exclamation-triangle me-1"></i>Regular (R)</span>',
            'M'     => '<span class="badge bg-danger text-white px-2 py-1"><i class="fas fa-times-circle me-1"></i>Malo (M)</span>',
            'BAJA'  => '<span class="badge bg-dark text-white px-2 py-1"><i class="fas fa-ban me-1"></i>Baja</span>',
            default => '<span class="badge bg-light text-dark px-2 py-1">' . e($this->condicion) . '</span>',
        };
    }

    public function getTipoAdquisicionLabelAttribute(): string
    {
        return match ($this->tipo_adquisicion) {
            'C'     => 'Compra',
            'D'     => 'Donación',
            default => $this->tipo_adquisicion,
        };
    }

    public function getTipoAdquisicionBadgeAttribute(): string
    {
        return match ($this->tipo_adquisicion) {
            'C'     => '<span class="badge bg-primary text-white px-2 py-1"><i class="fas fa-shopping-cart me-1"></i>Compra (C)</span>',
            'D'     => '<span class="badge bg-info text-white px-2 py-1"><i class="fas fa-hand-holding-heart me-1"></i>Donación (D)</span>',
            default => '<span class="badge bg-light text-dark px-2 py-1">' . e($this->tipo_adquisicion) . '</span>',
        };
    }

    public function getVerificationUrlAttribute(): string
    {
        return route('inventory.public_verify', $this->uuid);
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_path)) {
            return null;
        }
        if (str_starts_with($this->foto_path, 'http')) {
            return $this->foto_path;
        }
        return asset($this->foto_path);
    }
}
