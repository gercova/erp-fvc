<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelControlSlip extends Model
{
    use SoftDeletes, HasApprovals;

    protected $fillable = [
        'correlativo',
        'user_id',
        'area_id',
        'fecha',
        'hora',
        'requerimiento_nro',
        'orden_compra_nro',
        'nombre_grifo',
        'vehiculo_maquina',
        'placa',
        'kilometraje_horometro',
        'actividad_comision',
        'facturar_a',
        'observaciones',
        'atendido_por_grifo',
        'recibido_por',
        'total_general',
        'status',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_general' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FuelControlSlipItem::class);
    }
}
