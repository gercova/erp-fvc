<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgroHarvestQuickEntryValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => 'required|integer|exists:productive_activities,id',
            'production_campaign_id' => 'required|integer|exists:production_campaigns,id',
            'production_batch_id'    => 'nullable|integer|exists:production_batches,id',
            'agricultural_plot_id'   => 'nullable|integer|exists:agricultural_plots,id',
            'agricultural_nursery_id'=> 'nullable|integer|exists:agricultural_nurseries,id',
            'produced_item_id'       => 'required|integer|exists:produced_items,id',
            'harvest_date'           => 'required|date',
            'field_ticket_code'      => 'nullable|string|max:80',
            'quantity'               => 'required|numeric|min:0.001',
            'field_weight_kg'        => 'nullable|numeric|min:0',
            'quality_grade'          => 'required|string|max:50',
            'unit_cost_calculated'   => 'nullable|numeric|min:0',
            'warehouse_id'           => 'nullable|integer|exists:warehouses,id',
            'notes'                  => 'nullable|string',
        ];
    }
}
