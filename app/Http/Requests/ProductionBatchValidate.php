<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionBatchValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'production_campaign_id' => ['required', 'integer', 'exists:production_campaigns,id'],
            'batch_code'             => ['required', 'string', 'max:50'],
            'phase_name'             => ['required', 'string', 'max:100'],
            'phase_type'             => ['required', 'string', 'in:start,growth,fattening,sowing,maintenance,harvest,other'],
            'start_date'             => ['required', 'date'],
            'end_date'               => ['nullable', 'date', 'after_or_equal:start_date'],
            'initial_quantity'       => ['nullable', 'numeric', 'min:0'],
            'current_quantity'       => ['nullable', 'numeric', 'min:0'],
            'unit_measure'           => ['nullable', 'string', 'max:30'],
            'status'                 => ['required', 'string', 'in:active,completed,cancelled'],
            'notes'                  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'production_campaign_id.required' => 'Debe asociar la fase a una campaña.',
            'batch_code.required'             => 'El código de lote es obligatorio.',
            'phase_name.required'             => 'El nombre de la fase es obligatorio.',
            'phase_type.required'             => 'El tipo de fase es obligatorio.',
            'start_date.required'             => 'La fecha de inicio es requerida.',
        ];
    }
}
