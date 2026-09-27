<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProducedItemValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => ['required', 'integer', 'exists:productive_activities,id'],
            'name'                   => ['required', 'string', 'max:150'],
            'unit_of_measurement'   => ['required', 'string', 'max:30'],
            'standard_cost'          => ['nullable', 'numeric', 'min:0'],
            'description'            => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'productive_activity_id.required' => 'Debe asociar el producto a una actividad productiva.',
            'name.required'                   => 'El nombre del producto terminado es obligatorio.',
            'unit_of_measurement.required'   => 'La unidad de medida es obligatoria.',
        ];
    }
}
