<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgriculturalPlotValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->input('id');

        return [
            'productive_activity_id' => 'required|integer|exists:productive_activities,id',
            'code'                   => 'required|string|max:50|unique:agricultural_plots,code,' . $id,
            'name'                   => 'required|string|max:150',
            'sector_location'        => 'required|string|max:200',
            'area_hectares'          => 'required|numeric|min:0.01',
            'topography'             => 'nullable|string|max:80',
            'soil_type'              => 'nullable|string|max:100',
            'status'                 => 'required|string|in:IN_PRODUCTION,FALLOW_REST,PREPARATION,ABANDONED',
            'notes'                  => 'nullable|string',
        ];
    }
}
