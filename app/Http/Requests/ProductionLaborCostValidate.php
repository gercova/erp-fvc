<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionLaborCostValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'production_batch_id' => ['required', 'integer', 'exists:production_batches,id'],
            'task_description'    => ['required', 'string', 'max:200'],
            'worker_name'         => ['required', 'string', 'max:150'],
            'worker_user_id'      => ['nullable', 'integer', 'exists:users,id'],
            'task_date'           => ['required', 'date'],
            'hours_worked'        => ['required', 'numeric', 'min:0'],
            'hourly_rate'         => ['required', 'numeric', 'min:0'],
            'labor_cost'          => ['nullable', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'task_description.required' => 'La descripción de la labor es obligatoria.',
            'worker_name.required'      => 'El nombre del trabajador o cuadrilla es obligatorio.',
            'task_date.required'        => 'La fecha de la labor es obligatoria.',
        ];
    }
}
