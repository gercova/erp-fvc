<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductionRawMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_raw_materials';

    protected $fillable = [
        'uuid',
        'name',
        'category',
        'unit_of_measurement',
        'default_unit_cost',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductionRawMaterial $material) {
            if (empty($material->uuid)) {
                $material->uuid = (string) Str::uuid();
            }
        });
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductionInputMovement::class, 'production_raw_material_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'fertilizer'            => 'Fertilizante / Abono',
            'seed'                  => 'Semilla / Plantón',
            'medication'            => 'Medicamento / Fármaco',
            'feed'                  => 'Alimento / Balanceado',
            'insecticide_fungicide' => 'Insecticida / Fungicida',
            default                 => 'Otro / Suministro',
        };
    }
}
