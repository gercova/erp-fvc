<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleExitSlip extends Model
{
    use SoftDeletes, HasApprovals;

    protected $fillable = [
        'correlativo',
        'user_id',
        'area_id',
        'vehiculo',
        'solicitante_nombre',
        'chofer_nombre',
        'brevete_numero',
        'lugar',
        'motivo',
        'fecha_salida',
        'hora_salida',
        'fecha_retorno',
        'hora_retorno',
        'lugar_emision',
        'observaciones',
        'status',
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'fecha_retorno' => 'date',
    ];
}
