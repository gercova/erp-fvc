<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetInventory extends Model
{
    use HasFactory, SoftDeletes, HasApprovals;

    protected $fillable = [
        'correlativo',
        'area_id',
        'titulo',
        'periodo',
        'fecha_inventario',
        'responsable',
        'realizado_por',
        'status',
        'observaciones',
        'user_id',
    ];

    protected $casts = [
        'fecha_inventario' => 'date',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'area_id', 'area_id')->orderBy('orden');
    }

    public function getFormattedFechaInventarioAttribute(): string
    {
        return $this->fecha_inventario ? $this->fecha_inventario->format('d/m/Y') : '-';
    }

    public function getTotalAssetsAttribute(): int
    {
        return $this->assets()->count();
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->assets()->sum('costo');
    }
}
