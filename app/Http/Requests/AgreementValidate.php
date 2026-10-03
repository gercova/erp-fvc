<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgreementValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('agreement') ?? $this->route('id') ?? $this->id;
        if (is_object($id)) {
            $id = $id->id;
        }

        return [
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('agreements', 'code')->ignore($id),
            ],
            'type' => 'required|in:FRAMEWORK,SPECIFIC',
            'scope' => 'required|in:NATIONAL,INTERNATIONAL,REGIONAL,LOCAL',
            'objective' => 'required|string',
            'client_id' => [
                'required',
                'exists:clients,id',
                function ($attribute, $value, $fail) {
                    $client = Client::find($value);
                    if (!$client || empty($client->nro_documento)) {
                        $fail('La contraparte seleccionada debe contar con un número de documento/RUC válido.');
                    }
                },
            ],
            'area_id' => 'required|exists:areas,id',
            'coordinator_user_id' => 'nullable|exists:users,id',
            'productive_activity_id' => 'nullable|exists:productive_activities,id',
            'parent_agreement_id' => 'nullable|different:id|exists:agreements,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'signing_date' => 'nullable|date',
            'currency' => 'required|in:PEN,USD,EUR',
            'total_amount' => 'required|numeric|min:0',
            'has_economic_obligation' => 'nullable|boolean',
            'requires_mutual_reports' => 'nullable|boolean',
            'key_clauses' => 'nullable|string',
            'termination_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El título o nombre del convenio es obligatorio.',
            'type.required' => 'Debe especificar el tipo de convenio (Marco o Específico).',
            'scope.required' => 'Debe seleccionar el alcance geográfico/institucional.',
            'objective.required' => 'El objeto o propósito del convenio es obligatorio.',
            'client_id.required' => 'Debe seleccionar la contraparte o institución colaboradora.',
            'client_id.exists' => 'La contraparte seleccionada no existe en la base de datos.',
            'area_id.required' => 'Debe asignar el departamento o área institucional responsable.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'start_date.required' => 'La fecha de inicio de vigencia es obligatoria.',
            'end_date.required' => 'La fecha de culminación de vigencia es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de culminación debe ser posterior o igual a la fecha de inicio.',
            'currency.required' => 'Debe seleccionar la moneda del convenio.',
            'total_amount.required' => 'El monto total estimado o contraprestación es obligatorio.',
            'total_amount.min' => 'El monto no puede ser negativo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_economic_obligation' => filter_var($this->has_economic_obligation, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'requires_mutual_reports' => filter_var($this->requires_mutual_reports, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
        ]);
    }
}
