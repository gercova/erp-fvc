<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RdrInternalLoanValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => 'required|exists:productive_activities,id',
            'source_fund_id' => 'required|exists:fund_sources,id',
            'beneficiary_user_id' => 'required|exists:users,id',
            'loan_reason' => 'required|string|max:255',
            'amount_lent' => 'required|numeric|min:0.01',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'productive_activity_id.required' => 'Debe asociar el préstamo a una actividad productiva.',
            'source_fund_id.required' => 'Debe indicar la cuenta o fondo de donde sale el dinero.',
            'beneficiary_user_id.required' => 'Debe seleccionar el servidor o usuario beneficiario.',
            'loan_reason.required' => 'Debe ingresar el motivo de la habilitación / préstamo.',
            'amount_lent.required' => 'El monto es obligatorio.',
            'due_date.after_or_equal' => 'La fecha de rendición / devolución debe ser igual o posterior a la fecha de emisión.',
        ];
    }
}
