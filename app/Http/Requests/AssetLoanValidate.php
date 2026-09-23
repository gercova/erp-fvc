<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetLoanValidate extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function wantsJson(): bool {
        return $this->ajax() || parent::wantsJson();
    }

    public function rules(): array {
        return [
            'area_id'                => ['required', 'integer', 'exists:areas,id'],
            'asset_id'               => ['required', 'integer', 'exists:assets,id'],
            'borrower_type'          => ['required', 'string', 'in:STUDENT,FACULTY,ADMINISTRATIVE'],
            'borrower_user_id'       => ['nullable', 'integer', 'exists:users,id'],
            'borrower_name'          => ['required', 'string', 'max:255'],
            'borrower_document'      => ['required', 'string', 'max:50'],
            'borrower_code'          => ['nullable', 'string', 'max:50'],
            'borrower_career_or_area'=> ['nullable', 'string', 'max:255'],
            'borrower_phone'         => ['nullable', 'string', 'max:50'],
            'borrower_email'         => ['nullable', 'email', 'max:150'],
            'loan_date'              => ['required', 'date'],
            'expected_return_date'   => ['required', 'date'],
            'actual_return_date'     => ['nullable', 'date'],
            'status'                 => ['nullable', 'string', 'in:PRESTADO,DEVUELTO,DEVUELTO_OBSERVADO,EXTRAVIADO'],
            'initial_condition'      => ['nullable', 'string', 'in:B,R,M,BAJA'],
            'return_condition'       => ['nullable', 'string', 'in:B,R,M,BAJA'],
            'destination'            => ['required', 'string', 'max:255'],
            'purpose'                => ['nullable', 'string', 'max:1000'],
            'observations'           => ['nullable', 'string', 'max:1000'],
            'return_observations'    => ['nullable', 'string', 'max:1000'],
            'update_asset_condition' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array {
        return [
            'area_id.required'           => 'Debe seleccionar un departamento / área.',
            'area_id.exists'             => 'El área seleccionada no es válida.',
            'asset_id.required'          => 'Debe seleccionar un bien patrimonial para el préstamo.',
            'asset_id.exists'            => 'El bien patrimonial seleccionado no existe.',
            'borrower_type.required'     => 'Debe indicar el tipo de solicitante (Estudiante, Docente o Administrativo).',
            'borrower_type.in'           => 'El tipo de solicitante no es válido.',
            'borrower_name.required'     => 'El nombre completo del solicitante es obligatorio.',
            'borrower_document.required' => 'El número de documento de identidad (DNI/CE) es obligatorio.',
            'loan_date.required'         => 'La fecha de entrega del préstamo es obligatoria.',
            'expected_return_date.required' => 'La fecha estimada de devolución es obligatoria.',
            'destination.required'       => 'Debe indicar el ambiente o aula de destino del bien.',
        ];
    }
}
