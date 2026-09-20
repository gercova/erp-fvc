<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VacationExitSlip extends Model
{
    use SoftDeletes, HasApprovals;

    protected $fillable = [
        'correlativo',
        'user_id',
        'area_id',
        'apellidos_nombres',
        'dni',
        'condicion_laboral',
        'cargo_especialidad',
        'area_programa_estudios',
        'motivo',
        'fecha_desde',
        'fecha_hasta',
        'total_dias',
        'resolucion_directoral',
        'declaracion',
        'lugar_emision',
        'status',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'total_dias'  => 'integer',
    ];
}
