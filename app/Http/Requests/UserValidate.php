<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class UserValidate extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        $isUpdate   = $this->isMethod('PUT');
        $user       = $isUpdate ? $this->user : null;

        return [
            'nombres'       => ['required', 'string', 'max:255'],
            'user'          => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'user')->ignore($user?->id),
            ],
            'password'          => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
            'idcaja'            => ['required', 'integer', 'exists:cashes,id'],
            'warehouse_ids'     => ['required', 'array', 'min:1'],
            'warehouse_ids.*'   => ['required', 'integer', 'exists:warehouses,id'],
            'role'              => ['required', 'string', 'exists:roles,name'],
            'estado'            => ['required', 'integer', Rule::in([0, 1])],
            'area_id'           => ['nullable', 'integer', 'exists:areas,id'],
            'cargo'             => ['nullable', 'string', 'max:255'],
            'condicion_laboral' => ['nullable', 'string', 'max:255'],
            'firma_digital'     => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_firma_digital' => ['nullable'],
        ];
    }

    public function messages(): array {
        return [
            'warehouse_ids.required' => 'Debe asignar al menos un almacen.',
            'warehouse_ids.min'      => 'Debe asignar al menos un almacen.',
            'role.required'          => 'Debe seleccionar un rol.',
            'idcaja.required'        => 'Debe seleccionar una caja.',
            'password.required'      => 'Debe ingresar una contrasena.',
            'area_id.exists'         => 'El área seleccionada no es válida.',
            'firma_digital.mimes'    => 'La firma digital debe ser una imagen en formato JPG, PNG, WEBP o SVG.',
            'firma_digital.max'      => 'La imagen de firma no debe pesar más de 2MB.',
        ];
    }
}
