<?php

namespace App\Http\Requests\Claims;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolvePurchaseClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'refund_amount' => ['nullable', 'numeric', 'gt:0'],
            'resolution_notes' => ['nullable', 'required_if:decision,reject', 'string', 'max:1000'],
            'is_abusive' => ['nullable', 'boolean'],
            'deactivate_offer' => ['nullable', 'boolean'],
            'apply_trust_penalty' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Debés indicar una decisión (approve o reject).',
            'decision.in' => 'La decisión debe ser approve o reject.',
            'refund_amount.numeric' => 'El monto de reembolso debe ser numérico.',
            'refund_amount.gt' => 'El monto de reembolso debe ser mayor a 0.',
            'resolution_notes.required_if' => 'Debés explicar el motivo al rechazar un reclamo.',
            'resolution_notes.max' => 'Las notas de resolución no pueden superar los :max caracteres.',
        ];
    }
}
