<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionInputMovementValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'production_raw_material_id' => ['required', 'integer', 'exists:production_raw_materials,id'],
            'productive_activity_id'     => ['required', 'integer', 'exists:productive_activities,id'],
            'production_campaign_id'     => ['nullable', 'integer', 'exists:production_campaigns,id'],
            'production_batch_id'        => ['nullable', 'integer', 'exists:production_batches,id'],
            'movement_type'              => ['required', 'string', 'in:inflow_purchase,outflow_consumption,adjustment'],
            'movement_date'              => ['required', 'date'],
            'quantity'                   => ['required', 'numeric', 'gt:0'],
            'unit_cost'                  => ['required', 'numeric', 'min:0'],
            'total_cost'                 => ['nullable', 'numeric', 'min:0'],
            'buy_id'                     => ['nullable', 'integer', 'exists:buys,id'],
            'detail_buy_id'              => ['nullable', 'integer', 'exists:detail_buys,id'],
            'notes'                      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'production_raw_material_id.required' => 'Debe seleccionar un insumo / materia prima.',
            'productive_activity_id.required'     => 'Debe vincular el insumo a una actividad productiva específica.',
            'movement_type.required'              => 'El tipo de movimiento es obligatorio.',
            'quantity.required'                   => 'La cantidad debe ser mayor a cero.',
            'unit_cost.required'                  => 'El costo unitario es obligatorio.',
        ];
    }
}
