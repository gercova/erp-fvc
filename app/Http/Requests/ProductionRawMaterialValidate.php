<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionRawMaterialValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:150'],
            'category'            => ['required', 'string', 'in:fertilizer,seed,medication,feed,insecticide_fungicide,other'],
            'unit_of_measurement'=> ['required', 'string', 'max:30'],
            'default_unit_cost'   => ['nullable', 'numeric', 'min:0'],
            'description'         => ['nullable', 'string'],
            'is_active'           => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                => 'El nombre del insumo es obligatorio.',
            'category.required'            => 'La categoría del insumo es obligatoria.',
            'unit_of_measurement.required' => 'La unidad de medida es obligatoria.',
        ];
    }
}
