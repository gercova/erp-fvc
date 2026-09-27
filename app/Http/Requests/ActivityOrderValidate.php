<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityOrderValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'productive_activity_id' => ['required', 'integer', 'exists:productive_activities,id'],
            'client_id'              => ['required', 'integer', 'exists:clients,id'],
            'produced_item_id'       => ['required', 'integer', 'exists:produced_items,id'],
            'order_date'             => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'quantity'               => ['required', 'numeric', 'gt:0'],
            'unit_price'             => ['required', 'numeric', 'min:0'],
            'advance_payment'        => ['nullable', 'numeric', 'min:0'],
            'status'                 => ['nullable', 'string', 'in:pending,confirmed,delivered,invoiced,cancelled'],
            'notes'                  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'productive_activity_id.required' => 'Debe asociar el pedido a una actividad productiva.',
            'client_id.required'              => 'Debe seleccionar un cliente del padrón.',
            'produced_item_id.required'       => 'Debe seleccionar el producto a reservar / pedir.',
            'quantity.required'               => 'La cantidad solicitada debe ser mayor a cero.',
            'unit_price.required'             => 'El precio acordado es requerido.',
        ];
    }
}
