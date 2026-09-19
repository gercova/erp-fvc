<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelControlSlipItem extends Model
{
    protected $fillable = [
        'fuel_control_slip_id',
        'cantidad',
        'unidad_medida',
        'descripcion',
        'precio_unitario',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function fuelControlSlip(): BelongsTo
    {
        return $this->belongsTo(FuelControlSlip::class);
    }
}
