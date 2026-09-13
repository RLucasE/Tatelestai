<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackTemplateRequest extends FormRequest
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
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'allergens' => 'sometimes|nullable|array',
            'allergens.*' => 'string|max:100',
            'estimated_weight_kg' => 'sometimes|nullable|numeric|min:0|max:9999.99',
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
            'title.required' => 'El nombre de la plantilla es requerido',
            'description.required' => 'La descripción de la plantilla es requerida',
            'allergens.array' => 'Los alérgenos deben ser una lista',
            'estimated_weight_kg.numeric' => 'El peso estimado debe ser un número',
        ];
    }
}
