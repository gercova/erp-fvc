<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductionHarvestValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'produced_item_id'       => ['required', 'integer', 'exists:produced_items,id'],
            'production_campaign_id' => ['required', 'integer', 'exists:production_campaigns,id'],
            'production_batch_id'    => ['nullable', 'integer', 'exists:production_batches,id'],
            'harvest_date'           => ['required', 'date'],
            'quantity'               => ['required', 'numeric', 'gt:0'],
            'quality_grade'          => ['nullable', 'string', 'max:50'],
            'unit_cost_calculated'   => ['nullable', 'numeric', 'min:0'],
            'warehouse_id'           => ['nullable', 'integer', 'exists:warehouses,id'],
            'notes'                  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'produced_item_id.required'       => 'Debe seleccionar el producto cosechado / obtenido.',
            'production_campaign_id.required' => 'Debe asociar la cosecha a una campaña.',
            'harvest_date.required'           => 'La fecha de cosecha es obligatoria.',
            'quantity.required'               => 'La cantidad obtenida debe ser mayor a cero.',
        ];
    }
}
