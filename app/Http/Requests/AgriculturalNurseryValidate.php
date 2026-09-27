<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgriculturalNurseryValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => 'required|integer|exists:productive_activities,id',
            'production_batch_id'    => 'nullable|integer|exists:production_batches,id',
            'name'                   => 'required|string|max:150',
            'location'               => 'required|string|max:200',
            'species_name'           => 'required|string|max:150',
            'stage'                  => 'required|string|in:GERMINATION,SEEDLING_CONTAINER,HARDENING_ACCLIMATIZATION,READY_FOR_FIELD,DISPATCHED',
            'initial_quantity'       => 'required|integer|min:1',
            'current_quantity'       => 'required|integer|min:0',
            'sowing_date'            => 'required|date',
            'estimated_dispatch_date'=> 'nullable|date',
            'in_charge_user_id'      => 'nullable|integer|exists:users,id',
            'notes'                  => 'nullable|string',
        ];
    }
}
