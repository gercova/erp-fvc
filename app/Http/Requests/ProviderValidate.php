<?php

namespace App\Http\Requests;

use App\Models\IdentityDocumentType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProviderValidate extends FormRequest
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
        $providerId = $this->input('id');
        $tipoDocId = (int) $this->input('tipo_documento');

        return [
            'tipo_documento' => ['required', 'integer', 'exists:identity_document_types,id'],
            'dni_ruc' => [
                'required',
                'string',
                'max:15',
                Rule::unique('providers', 'nro_documento')
                    ->where(fn ($query) => $query->where('iddoc', $tipoDocId))
                    ->ignore($providerId),
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'direccion' => ['required', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:2'],
            'provincia' => ['nullable', 'string', 'max:4'],
            'distrito' => ['nullable', 'string', 'max:6'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'Debe seleccionar el tipo de documento.',
            'tipo_documento.exists' => 'El tipo de documento seleccionado no es válido.',
            'dni_ruc.required' => 'Debe ingresar el número del documento.',
            'dni_ruc.unique' => 'El proveedor con este tipo y número de documento ya se encuentra registrado.',
            'razon_social.required' => 'Debe ingresar el nombre o razón social.',
            'direccion.required' => 'Debe ingresar la dirección.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $tipoDocId = (int) $this->input('tipo_documento');
            $docNumber = trim((string) $this->input('dni_ruc'));

            if ($tipoDocId && $docNumber !== '') {
                $docType = IdentityDocumentType::find($tipoDocId);
                $code = $docType?->codigo ? trim((string) $docType->codigo) : '';

                if ($code === '1' && ! preg_match('/^\d{8}$/', $docNumber)) {
                    $validator->errors()->add('dni_ruc', 'Para DNI debe ingresar exactamente 8 dígitos numéricos.');
                } elseif ($code === '6' && ! preg_match('/^\d{11}$/', $docNumber)) {
                    $validator->errors()->add('dni_ruc', 'Para RUC debe ingresar exactamente 11 dígitos numéricos.');
                } elseif (in_array($code, ['4', '7', 'A', '0'], true) && ! preg_match('/^[A-Za-z0-9\-]{3,15}$/', $docNumber)) {
                    $validator->errors()->add('dni_ruc', 'Para este tipo de documento solo se permiten letras, números y guión (3-15 caracteres).');
                }
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'status' => false,
            'msg' => $validator->errors()->first(),
            'errors' => $validator->errors(),
            'type' => 'warning',
        ], 422);

        throw new HttpResponseException($response);
    }
}
