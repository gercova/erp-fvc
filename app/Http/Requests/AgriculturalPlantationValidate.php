<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgriculturalPlantationValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agricultural_plot_id'    => 'required|integer|exists:agricultural_plots,id',
            'crop_species'            => 'required|string|max:150',
            'crop_type'               => 'required|string|in:PERMANENT,SHORT_CYCLE',
            'variety'                 => 'nullable|string|max:100',
            'planting_date'           => 'required|date',
            'estimated_harvest_date'  => 'nullable|date',
            'harvest_frequency_days'  => 'nullable|integer|min:1',
            'plant_count'             => 'required|integer|min:0',
            'spacing_meters'          => 'nullable|string|max:50',
            'expected_yield_per_ha'   => 'nullable|numeric|min:0',
            'age_years'               => 'nullable|numeric|min:0',
            'status'                  => 'required|string|in:VEGETATIVE_DEVELOPMENT,FULL_PRODUCTION,DECLINING,RENOVATION,ERADICATED',
            'notes'                   => 'nullable|string',
        ];
    }
}
