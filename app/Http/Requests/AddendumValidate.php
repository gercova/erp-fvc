<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class AddendumValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agreementId = $this->route('id') ?? $this->route('agreement') ?? $this->input('agreement_id');
        if (is_object($agreementId)) {
            $agreementId = $agreementId->id;
        }

        return [
            'agreement_id' => 'required|exists:agreements,id',
            'type' => 'required|in:TERM_EXTENSION,AMOUNT_EXTENSION,SCOPE_MODIFICATION,MIXED,OTHER',
            'resolution_number' => 'nullable|string|max:100',
            'justification' => 'required|string',
            'new_end_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($agreementId) {
                    if ($value && $agreementId) {
                        $agreement = Agreement::find($agreementId);
                        if ($agreement && $agreement->start_date && $value < $agreement->start_date->format('Y-m-d')) {
                            $fail('La nueva fecha de vigencia debe ser posterior al inicio del convenio.');
                        }
                    }
                },
            ],
            'amount_delta' => 'nullable|numeric',
            'signature_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480',
        ];
    }

    public function messages(): array
    {
        return [
            'agreement_id.required' => 'El convenio institucional asociado es obligatorio.',
            'agreement_id.exists' => 'El convenio seleccionado no existe.',
            'type.required' => 'Debe seleccionar el tipo de adenda (Plazo, Monto, Modificación o Mixta).',
            'type.in' => 'El tipo de adenda seleccionado no es válido.',
            'justification.required' => 'Debe registrar la justificación u objeto sustantivo de la adenda.',
            'file.max' => 'El documento adjunto no debe exceder los 20MB.',
            'file.mimes' => 'El formato del archivo adjunto debe ser PDF, Word o imagen.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $agreementId = $this->route('id') ?? $this->route('agreement') ?? $this->input('agreement_id');
        if (is_object($agreementId)) {
            $agreementId = $agreementId->id;
        }
        if (!$this->has('agreement_id') && $agreementId) {
            $this->merge(['agreement_id' => $agreementId]);
        }
        if ($this->has('amount_delta') && $this->amount_delta === null) {
            $this->merge(['amount_delta' => 0.00]);
        }
    }
}
