<?php

namespace App\Http\Requests\Verification;

use App\Enums\TrustScoreCriterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchReviewDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*.id' => ['required', 'integer'],
            'files.*.status' => ['required', 'string', 'in:pending,accepted,rejected'],
            'files.*.document_category' => ['required', 'string', Rule::in(TrustScoreCriterion::values())],
            'files.*.rejection_reason' => ['nullable', 'string', 'max:1000'],
            'files.*.points' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Debes enviar al menos un archivo para revisar',
            'files.*.status.in' => 'El estado debe ser pending, accepted o rejected',
            'files.*.document_category.in' => 'La categoría seleccionada no es válida',
            'files.*.points.integer' => 'Los puntos deben ser un número entero',
            'files.*.points.min' => 'Los puntos no pueden ser negativos',
            'files.*.points.max' => 'Los puntos no pueden superar 50',
        ];
    }
}
