<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AreaValidate extends FormRequest
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
        $areaId = $this->input('id');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('areas', 'code')->ignore($areaId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                'string',
                Rule::in(['direccion_general', 'area', 'unidad', 'programa_academico', 'organo_consultivo']),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:areas,id',
                function ($attribute, $value, $fail) use ($areaId) {
                    if ($areaId && (int) $value === (int) $areaId) {
                        $fail('Un área no puede depender de sí misma.');
                    }
                },
            ],
            'head_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_advisory' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código de área es obligatorio.',
            'code.unique' => 'Ya existe un área registrada con este código.',
            'code.max' => 'El código no debe superar los 50 caracteres.',
            'name.required' => 'El nombre del área es obligatorio.',
            'name.max' => 'El nombre no debe superar los 255 caracteres.',
            'type.required' => 'Debe seleccionar un tipo de área válido.',
            'type.in' => 'El tipo de área seleccionado no es válido.',
            'parent_id.exists' => 'El área dependiente / superior seleccionada no existe.',
            'head_user_id.exists' => 'El responsable seleccionado no existe.',
            'level.integer' => 'El nivel jerárquico debe ser un número entero.',
            'level.min' => 'El nivel jerárquico mínimo es 1.',
        ];
    }
}
