<?php

namespace App\Http\Requests\Verification;

use Illuminate\Foundation\Http\FormRequest;

class VerifyGooglePlacesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_verified' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_verified.required' => 'El estado de verificación es requerido.',
            'is_verified.boolean' => 'El estado de verificación debe ser un valor booleano.',
            'notes.max' => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }
}
