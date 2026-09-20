<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VacationExitSlipValidate extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function wantsJson(): bool {
        return $this->ajax() || parent::wantsJson();
    }

    public function rules(): array {
        $rules = [
            'apellidos_nombres'         => 'required|string|max:255',
            'dni'                       => 'required|string|max:20',
            'condicion_laboral'         => 'required|string|max:100',
            'cargo_especialidad'        => 'required|string|max:255',
            'area_programa_estudios'    => 'required|string|max:255',
            'area_id'                   => 'nullable|exists:areas,id',
            'fecha_desde'               => 'required|date',
            'fecha_hasta'               => 'required|date|after_or_equal:fecha_desde',
            'total_dias'                => 'required|integer|min:1',
            'resolucion_directoral'     => 'nullable|string|max:255',
        ];

        // El correlativo solo se valida como requerido y único al crear
        if ($this->isMethod('POST')) {
            $rules['correlativo'] = 'required|unique:vacation_exit_slips,correlativo';
        }

        return $rules;
    }
    
    public function messages(): array {
        return [
            'correlativo.required'              => 'El número de vale es requerido',
            'correlativo.unique'                => 'El número de vale ya existe',
            'apellidos_nombres.required'        => 'Los apellidos y nombres son requeridos',
            'dni.required'                      => 'El DNI es requerido',
            'condicion_laboral.required'        => 'La condición laboral es requerida',
            'cargo_especialidad.required'       => 'El cargo o especialidad es requerido',
            'area_programa_estudios.required'   => 'El área, programa o estudios es requerido',
            'area_id.exists'                    => 'El área no existe',
            'fecha_desde.required'              => 'La fecha de inicio es requerida',
            'fecha_hasta.required'              => 'La fecha de fin es requerida',
            'fecha_hasta.after_or_equal'        => 'La fecha de fin debe ser mayor o igual a la fecha de inicio',
            'total_dias.required'               => 'El total de días es requerido',
            'total_dias.min'                    => 'El total de días debe ser mayor a 0',
        ];
    }
}
