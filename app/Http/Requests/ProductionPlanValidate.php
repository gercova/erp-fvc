<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionPlanValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => ['required', 'integer', 'exists:productive_activities,id'],
            'campaign_code'          => ['nullable', 'string', 'max:50'],
            'name'                   => ['required', 'string', 'max:150'],
            'start_date'             => ['required', 'date'],
            'end_date'               => ['nullable', 'date', 'after_or_equal:start_date'],
            'production_targets'     => ['nullable', 'string'],
            'target_quantity'        => ['nullable', 'numeric', 'min:0'],
            'target_unit'            => ['nullable', 'string', 'max:30'],
            'budget_allocated'       => ['nullable', 'numeric', 'min:0'],
            'status'                 => ['required', 'string', 'in:planned,in_progress,closed'],
            'notes'                  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'productive_activity_id.required' => 'Debe seleccionar una actividad productiva.',
            'name.required'                   => 'El nombre de la campaña o plan es obligatorio.',
            'start_date.required'             => 'La fecha de inicio es obligatoria.',
            'status.in'                       => 'El estado debe ser planned, in_progress o closed.',
        ];
    }
}
