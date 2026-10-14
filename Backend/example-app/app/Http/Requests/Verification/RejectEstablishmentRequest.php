<?php

namespace App\Http\Requests\Verification;

use Illuminate\Foundation\Http\FormRequest;

class RejectEstablishmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Debes indicar el motivo del rechazo.',
            'reason.min' => 'El motivo del rechazo debe tener al menos 10 caracteres.',
            'reason.max' => 'El motivo del rechazo no puede superar los 1000 caracteres.',
        ];
    }
}
