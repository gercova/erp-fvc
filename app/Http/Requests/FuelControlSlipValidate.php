<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FuelControlSlipValidate extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    /**
     * Ensure AJAX (jQuery) calls receive JSON validation errors
     * instead of a browser redirect, even when Accept header is not set.
     */
    public function wantsJson(): bool {
        return $this->ajax() || parent::wantsJson();
    }

    public function rules(): array {
        $rules = [
            'fecha'                 => 'required|date',
            'hora'                  => 'required',
            'requerimiento_nro'     => 'nullable|string|max:50',
            'orden_compra_nro'      => 'nullable|string|max:50',
            'nombre_grifo'          => 'required|string|max:255',
            'vehiculo_maquina'      => 'required|string|max:255',
            'placa'                 => 'nullable|string|max:50',
            'kilometraje_horometro' => 'nullable|string|max:50',
            'actividad_comision'    => 'required|string',
            'facturar_a'            => 'required|string|max:255',
            'area_id'               => 'nullable|exists:areas,id',
            'observaciones'         => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.cantidad'      => 'required|numeric|min:0.01',
            'items.*.unidad_medida' => 'required|string|max:20',
            'items.*.descripcion'       => 'required|string|max:255',
            'items.*.precio_unitario'   => 'nullable|numeric|min:0',
        ];

        // correlativo is only submitted (and validated) on create/store
        if ($this->isMethod('POST') && !$this->route('fuel_control_slip')) {
            $rules['correlativo'] = 'required|unique:fuel_control_slips,correlativo';
        }

        return $rules;
    }

    public function messages(): array {
        return [
            'correlativo.required'          => 'El correlativo es obligatorio.',
            'correlativo.unique'            => 'El correlativo ya existe.',
            'fecha.required'                => 'La fecha es obligatoria.',
            'hora.required'                 => 'La hora es obligatoria.',
            'nombre_grifo.required'         => 'El nombre del grifo es obligatorio.',
            'vehiculo_maquina.required'     => 'El vehículo o máquina es obligatorio.',
            'actividad_comision.required'   => 'La actividad o comisión es obligatoria.',
            'facturar_a.required'           => 'El campo facturar a es obligatorio.',
            'items.required'                => 'Debe agregar al menos un item.',
            'items.*.cantidad.required'     => 'La cantidad es obligatoria.',
            'items.*.cantidad.min'          => 'La cantidad debe ser mayor a 0.',
            'items.*.unidad_medida.required'    => 'La unidad de medida es obligatoria.',
            'items.*.descripcion.required'      => 'La descripción es obligatoria.',
            'items.*.precio_unitario.required'  => 'El precio unitario es obligatorio.',
        ];
    }
}
