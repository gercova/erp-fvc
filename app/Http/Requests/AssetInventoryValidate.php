<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetInventoryValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function wantsJson(): bool
    {
        return $this->ajax() || parent::wantsJson();
    }

    public function rules(): array
    {
        return [
            'area_id'          => ['required', 'integer', 'exists:areas,id'],
            'titulo'           => ['required', 'string', 'max:255'],
            'periodo'          => ['required', 'string', 'max:20'],
            'fecha_inventario' => ['required', 'date'],
            'responsable'      => ['required', 'string', 'max:255'],
            'realizado_por'    => ['required', 'string', 'max:255'],
            'observaciones'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'area_id.required'          => 'El área del inventario es obligatoria.',
            'titulo.required'           => 'El título del inventario es obligatorio.',
            'periodo.required'          => 'El período o año es obligatorio.',
            'fecha_inventario.required' => 'La fecha de realización del inventario es obligatoria.',
            'responsable.required'      => 'El nombre del responsable del área es obligatorio.',
            'realizado_por.required'    => 'El nombre de la persona que levantó el inventario es obligatorio.',
        ];
    }
}
