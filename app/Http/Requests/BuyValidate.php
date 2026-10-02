<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuyValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $providerId = $this->input('dni_ruc');
        $voucherTypeId = $this->input('idtipo_comprobante');
        $serie = mb_strtoupper(trim((string) $this->input('serie')));
        $correlativo = trim((string) $this->input('correlativo'));

        return [
            'idtipo_comprobante' => ['required', 'integer', 'exists:type_documents,id'],
            'serie' => ['required', 'string', 'max:10'],
            'correlativo' => [
                'required',
                'string',
                'max:20',
                Rule::unique('buys', 'correlativo')->where(function ($query) use ($providerId, $voucherTypeId, $serie, $correlativo) {
                    return $query->where('idproveedor', $providerId)
                        ->where('idtipo_comprobante', $voucherTypeId)
                        ->where('serie', $serie)
                        ->where('correlativo', $correlativo);
                }),
            ],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'dni_ruc' => ['required', 'integer', 'exists:providers,id'],
            'idalmacen' => ['required', 'integer', 'exists:warehouses,id'],
            'modo_pago' => ['required', 'integer', 'exists:pay_modes,id'],
            'condicion_pago' => ['nullable', 'string', 'in:Contado,Credito'],
            'monto_credito' => ['nullable', 'numeric', 'min:0'],
            'cuotas' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'idtipo_comprobante.required' => 'Debe seleccionar el tipo de comprobante.',
            'idtipo_comprobante.exists' => 'El tipo de comprobante seleccionado no es válido.',
            'serie.required' => 'Debe ingresar la serie.',
            'correlativo.required' => 'Debe ingresar el número.',
            'correlativo.unique' => 'Ya existe un comprobante registrado con este proveedor, tipo, serie y número.',
            'fecha_emision.required' => 'Debe ingresar la fecha de emisión.',
            'fecha_vencimiento.required' => 'Debe ingresar la fecha de vencimiento.',
            'fecha_vencimiento.after_or_equal' => 'La fecha de vencimiento no puede ser menor a la fecha de emisión.',
            'dni_ruc.required' => 'Debe seleccionar el proveedor.',
            'dni_ruc.exists' => 'El proveedor seleccionado no existe.',
            'idalmacen.required' => 'Debe seleccionar el almacén de destino.',
            'idalmacen.exists' => 'El almacén de destino seleccionado no existe.',
            'modo_pago.required' => 'Debe seleccionar el modo de pago.',
            'modo_pago.exists' => 'El modo de pago seleccionado no es válido.',
        ];
    }
}
