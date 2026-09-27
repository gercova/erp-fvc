<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityTransactionValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => 'required|exists:productive_activities,id',
            'productive_unit_id' => 'nullable|exists:productive_units,id',
            'category_id' => 'required|exists:activity_transaction_categories,id',
            'fund_source_id' => 'required|exists:fund_sources,id',
            'transaction_type' => 'required|in:INCOME,EXPENSE',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2050',
            'voucher_type' => 'nullable|string|max:50',
            'voucher_number' => 'nullable|string|max:80',
            'concept' => 'required|string|max:500',
            'beneficiary_or_payer' => 'nullable|string|max:180',
            'cash_id' => 'nullable|exists:cashes,id',
            'buy_id' => 'nullable|exists:buys,id',
            'sale_note_id' => 'nullable|exists:sale_notes,id',
            'billing_id' => 'nullable|exists:billings,id',
            'status' => 'nullable|in:REGISTERED,VERIFIED,ANULLED',
        ];
    }

    public function messages(): array
    {
        return [
            'productive_activity_id.required' => 'Debe asociar el movimiento a una actividad productiva.',
            'category_id.required' => 'Debe seleccionar una categoría de ingreso o gasto.',
            'fund_source_id.required' => 'Debe indicar la fuente de financiamiento o cuenta bancaria.',
            'transaction_type.required' => 'Debe indicar si es ingreso o egreso.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'transaction_date.required' => 'La fecha de la transacción es obligatoria.',
            'concept.required' => 'El concepto o glosa del movimiento es obligatorio.',
        ];
    }
}
