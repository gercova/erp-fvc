<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductiveActivityValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'name' => 'required|string|max:150',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('productive_activities', 'code')->ignore($id),
            ],
            'type' => 'required|in:AGRICULTURAL,FORESTRY,AQUACULTURE,LIVESTOCK,INSTITUTIONAL,SERVICES',
            'cost_center_code' => 'nullable|string|max:50',
            'area_id' => 'required|exists:areas,id',
            'head_user_id' => 'nullable|exists:users,id',
            'default_fund_source_id' => 'nullable|exists:fund_sources,id',
            'status' => 'required|in:ACTIVA,INACTIVA,EN_MANTENIMIENTO,CERRADA',
            'execution_progress_percent' => 'nullable|numeric|min:0|max:100',
            'monitoring_observations' => 'nullable|string',
            'description' => 'nullable|string',
            'order_index' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la actividad productiva es obligatorio.',
            'code.required' => 'El código identificador es obligatorio.',
            'code.unique' => 'El código ya ha sido registrado en otra actividad.',
            'type.required' => 'Debe seleccionar el tipo de actividad productiva.',
            'type.in' => 'El tipo seleccionado no es válido.',
            'area_id.required' => 'Debe seleccionar el área institucional responsable.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'status.required' => 'El estado de la actividad es obligatorio.',
        ];
    }
}
