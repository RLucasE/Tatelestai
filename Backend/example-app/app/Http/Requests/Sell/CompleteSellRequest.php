<?php

namespace App\Http\Requests\Sell;

use Illuminate\Foundation\Http\FormRequest;

class CompleteSellRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara y normaliza el código de pickup recibido antes de la validación.
     * Soporta tanto el código alfanumérico limpio como el payload estructurado del QR (TAT:<pickup_code>:<sell_id>).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('pick_up_code') && is_string($this->input('pick_up_code'))) {
            $raw = strtoupper(trim($this->input('pick_up_code')));
            if (str_starts_with($raw, 'TAT:')) {
                $parts = explode(':', $raw);
                $raw = $parts[1] ?? $raw;
            }
            $this->merge(['pick_up_code' => $raw]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pick_up_code' => ['required', 'string'],
        ];
    }
}
