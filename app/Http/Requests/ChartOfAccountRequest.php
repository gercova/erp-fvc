<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChartOfAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->route('chart_of_account') ?? $this->route('id') ?? $this->input('id');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts', 'code')->ignore($id),
            ],
            'name'                 => 'required|string|max:255',
            'element'              => 'required|integer|between:0,9',
            'level'                => 'required|integer|between:1,5',
            'nature'               => 'required|in:DEBIT,CREDIT',
            'classification'       => 'required|in:ACTIVO,PASIVO,PATRIMONIO,GASTOS_NATURALEZA,INGRESOS,COSTOS_PRODUCCION,GASTOS_FUNCION,ORDEN',
            'accepts_movements'    => 'nullable|boolean',
            'allows_movement'      => 'nullable|boolean',
            'requires_third_party' => 'nullable|boolean',
            'requires_cost_center' => 'nullable|boolean',
            'requires_fund_source' => 'nullable|boolean',
            'currency'             => 'nullable|string|size:3',
            'parent_id'            => 'nullable|exists:chart_of_accounts,id',
            'active'               => 'nullable|boolean',
            'is_active'            => 'nullable|boolean',
        ];
    }
}
