<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class ServiceEngagementValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technological_service_id'     => 'required|exists:technological_services,id',
            'client_id'                    => 'required|exists:clients,id',
            'agreement_id'                 => 'nullable|exists:agreements,id',
            'productive_activity_id'       => 'nullable|exists:productive_activities,id',
            'responsible_user_id'          => 'required|exists:users,id',
            'description'                  => 'required|string',
            'delivery_modality'            => 'required|in:IN_PERSON,VIRTUAL,HYBRID,FIELD',
            'contracted_hours'             => 'nullable|numeric|min:0',
            'hourly_rate'                  => 'nullable|numeric|min:0',
            'quantity'                     => 'nullable|numeric|min:0.01',
            'unit_price'                   => 'required|numeric|min:0',
            'total_amount'                 => 'nullable|numeric|min:0',
            'currency'                     => 'required|in:PEN,USD',
            'start_date'                   => 'required|date',
            'expected_delivery_date'       => 'required|date|after_or_equal:start_date',
            'min_attendance_percent'       => 'nullable|numeric|min:0|max:100',
            'allow_pool_overage'           => 'nullable|boolean',
            'low_balance_threshold_hours'  => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // If under agreement, validate that the total service amount does not exceed agreement total amount
            if ($this->filled('agreement_id')) {
                $agreement = Agreement::find($this->input('agreement_id'));
                if ($agreement) {
                    $newAmount = (float) ($this->input('total_amount') ?? ($this->input('quantity', 1) * $this->input('unit_price', 0)));
                    $existingEngagementsTotal = (float) $agreement->serviceEngagements()
                        ->when($this->route('id'), fn($q) => $q->where('id', '!=', $this->route('id')))
                        ->sum('total_amount');

                    if (($existingEngagementsTotal + $newAmount) > (float) $agreement->total_amount) {
                        $available = max(0, (float) $agreement->total_amount - $existingEngagementsTotal);
                        $v->errors()->add('total_amount', "El monto del servicio excede el saldo presupuestal disponible del convenio (Disponible: S/ " . number_format($available, 2) . ").");
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'technological_service_id.required' => 'Debe seleccionar un servicio tecnológico del catálogo.',
            'client_id.required'                => 'Debe seleccionar un cliente o contraparte contratante.',
            'responsible_user_id.required'      => 'Debe asignar a un especialista responsable del servicio.',
            'start_date.required'               => 'La fecha de inicio es obligatoria.',
            'expected_delivery_date.required'   => 'La fecha comprometida de entrega o fin es obligatoria.',
            'expected_delivery_date.after_or_equal' => 'La fecha de entrega debe ser igual o posterior a la fecha de inicio.',
            'unit_price.required'               => 'El precio o tarifa unitaria es obligatoria.',
        ];
    }
}
