<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExitSlip extends Model
{
    use SoftDeletes, HasApprovals;

    protected $fillable = [
        'correlativo',
        'user_id',
        'area_id',
        'nombres_apellidos',
        'tipo_personal',
        'fecha_salida',
        'hora_salida',
        'fecha_retorno',
        'hora_retorno',
        'destino',
        'motivo',
        'motivo_especificar',
        'lugar',
        'observaciones',
        'lugar_emision',
        'nota_salud',
        'status',
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'fecha_retorno' => 'date',
    ];
}
