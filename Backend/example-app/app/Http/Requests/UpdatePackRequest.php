<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'price' => 'sometimes|required_with:minimum_value|numeric|min:1',
            'minimum_value' => 'sometimes|required_with:price|numeric|min:1|gte:price',
            'quantity' => 'sometimes|integer|min:1',
            'pickup_start_datetime' => 'sometimes|required_with:pickup_end_datetime|date',
            'pickup_end_datetime' => 'sometimes|required_with:pickup_start_datetime|date|after:pickup_start_datetime',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'minimum_value.gte' => 'El valor mínimo garantizado debe ser mayor o igual al precio del pack',
            'pickup_end_datetime.after' => 'El fin de la ventana de retiro debe ser posterior al inicio',
        ];
    }
}
