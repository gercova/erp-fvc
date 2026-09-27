<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LivestockUnitValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => 'required|integer|exists:productive_activities,id',
            'productive_unit_id'     => 'nullable|integer|exists:productive_units,id',
            'species'                => 'required|string|in:CATTLE,PIG,GUINEA_PIG,POULTRY,FISH_POND,SHEEP_GOAT,OTHER',
            'breed'                  => 'nullable|string|max:100',
            'tracking_type'          => 'required|string|in:INDIVIDUAL,BATCH',
            'identifier_code'        => 'required|string|max:80',
            'batch_head_count'       => 'required|integer|min:1',
            'sex'                    => 'required|string|in:MALE,FEMALE,MIXED_BATCH',
            'birth_or_entry_date'    => 'required|date',
            'entry_weight_kg'        => 'nullable|numeric|min:0',
            'current_weight_kg'      => 'nullable|numeric|min:0',
            'status'                 => 'required|string|in:ACTIVE,GESTATION,LACTATION,FATTENING,QUARANTINE,SOLD,SLAUGHTERED,DECEASED,TRANSFERRED',
            'notes'                  => 'nullable|string',
        ];
    }
}
