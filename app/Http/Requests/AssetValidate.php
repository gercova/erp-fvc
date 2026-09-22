<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetValidate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function wantsJson(): bool
    {
        return $this->ajax() || parent::wantsJson();
    }

    public function rules(): array
    {
        return [
            'area_id'           => ['required', 'integer', 'exists:areas,id'],
            'orden'             => ['nullable', 'integer', 'min:1'],
            'codigo_producto'   => ['nullable', 'string', 'max:50'],
            'codigo'            => ['nullable', 'string', 'max:50'],
            'descripcion'       => ['required', 'string', 'max:1000'],
            'marca'             => ['nullable', 'string', 'max:100'],
            'modelo'            => ['nullable', 'string', 'max:100'],
            'serie'             => ['nullable', 'string', 'max:100'],
            'costo'             => ['nullable', 'numeric', 'min:0'],
            'condicion'         => ['required', 'string', 'in:B,R,M,BAJA,G,F,P,W'],
            'tipo_adquisicion'  => ['required', 'string', 'in:C,D,P'],
            'fecha_adquisicion' => ['nullable', 'date'],
            'anio_adquisicion'  => ['nullable', 'string', 'max:20'],
            'ubicacion'         => ['required', 'string', 'max:255'],
            'custodio'          => ['nullable', 'string', 'max:255'],
            'observaciones'     => ['nullable', 'string', 'max:2000'],
            'estado_operativo'  => ['nullable', 'string', 'in:OPERATIVO,EN_REPARACION,INOPERATIVO,DE_BAJA'],
            'foto'              => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ];
    }

    public function messages(): array
    {
        return [
            'area_id.required'           => 'Debe seleccionar un departamento / área.',
            'area_id.exists'             => 'El área seleccionada no es válida.',
            'descripcion.required'       => 'La descripción detallada del bien es obligatoria.',
            'descripcion.max'            => 'La descripción no debe exceder 1000 caracteres.',
            'condicion.required'         => 'Debe especificar la condición del bien (Bueno, Regular, Malo o Baja).',
            'condicion.in'               => 'La condición seleccionada no es válida.',
            'tipo_adquisicion.required'  => 'Debe indicar el tipo de adquisición (Compra o Donación).',
            'tipo_adquisicion.in'        => 'El tipo de adquisición debe ser Compra o Donación.',
            'costo.numeric'              => 'El costo debe ser un valor numérico.',
            'costo.min'                  => 'El costo no puede ser negativo.',
            'ubicacion.required'         => 'La ubicación física del activo es obligatoria.',
            'foto.image'                 => 'El archivo adjunto debe ser una imagen válida.',
            'foto.max'                   => 'La imagen no debe pesar más de 3MB.',
        ];
    }
}
