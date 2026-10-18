<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
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
            'sell_id' => ['required', 'integer', 'exists:sells,id'],
            'payment_id' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'preference_id' => ['nullable', 'string'],
        ];
    }
}
