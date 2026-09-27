<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RdrCutTransferValidate extends FormRequest
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
            'cut_account_number' => 'required|string|max:80',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
            'bank_operation_number' => 'required|string|max:80',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'productive_activity_id.required' => 'Debe asociar la transferencia a una actividad productiva.',
            'source_fund_id.required' => 'Debe seleccionar la cuenta origen de los fondos.',
            'cut_account_number.required' => 'Debe ingresar la cuenta CUT de destino.',
            'amount.required' => 'El monto a transferir es obligatorio.',
            'transfer_date.required' => 'La fecha de transferencia es obligatoria.',
            'bank_operation_number.required' => 'El número de operación bancaria es obligatorio.',
        ];
    }
}
