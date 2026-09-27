<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LivestockEventValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'livestock_unit_id'          => 'required|integer|exists:livestock_units,id',
            'event_type'                 => 'required|string|in:HEALTH_TREATMENT,VACCINATION,FEEDING_LOG,WEIGHT_CONTROL,BREEDING_SERVICE,BIRTH,WEANING,MORTALITY_DISPOSAL,SALE_TRANSFER',
            'event_date'                 => 'required|date',
            'description'                => 'required|string|max:500',
            'dosage_or_ration'           => 'nullable|string|max:100',
            'feeding_phase'              => 'nullable|string|in:STARTER,GROWER,FINISHER,MAINTENANCE,LACTATION,OTHER',
            'head_affected_count'        => 'required|integer|min:1',
            'production_raw_material_id' => 'nullable|integer|exists:production_raw_materials,id',
            'input_quantity_used'        => 'nullable|numeric|min:0',
            'input_cost'                 => 'nullable|numeric|min:0',
            'labor_cost'                 => 'nullable|numeric|min:0',
            'notes'                      => 'nullable|string',
        ];
    }
}
