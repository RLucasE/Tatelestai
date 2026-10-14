<?php

namespace App\Http\Requests\Verification;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'call_successful' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'call_successful.required' => 'El resultado de la llamada es requerido.',
            'call_successful.boolean' => 'El resultado de la llamada debe ser un valor booleano.',
            'notes.max' => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }
}
