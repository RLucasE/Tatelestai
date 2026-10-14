<?php

namespace App\Http\Requests\Verification;

use App\Enums\TrustScoreCriterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:accept,reject'],
            'rejection_reason' => ['nullable', 'string', 'max:1000', 'required_if:decision,reject'],
            'override_category' => ['nullable', 'string', Rule::in(TrustScoreCriterion::values())],
            'points' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Debes especificar una decisión (accept o reject)',
            'decision.in' => 'La decisión debe ser accept o reject',
            'rejection_reason.required_if' => 'Debes indicar el motivo de rechazo',
            'override_category.in' => 'La categoría seleccionada no es válida',
            'points.integer' => 'Los puntos deben ser un número entero',
            'points.min' => 'Los puntos no pueden ser negativos',
            'points.max' => 'Los puntos no pueden superar 50',
        ];
    }
}
