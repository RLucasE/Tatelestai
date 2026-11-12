<?php

namespace App\Http\Requests\Claims;

use App\Enums\ClaimReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseClaimRequest extends FormRequest
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
        $maxFiles = (int) config('claims.max_evidences_per_claim', 5);
        $maxSizeKb = (int) config('claims.max_evidence_size_kb', 5120);

        return [
            'reason' => ['required', 'string', Rule::enum(ClaimReason::class)],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'evidences' => ['nullable', 'array', "max:{$maxFiles}"],
            'evidences.*' => ['image', 'mimes:jpg,jpeg,png,webp', "max:{$maxSizeKb}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxFiles = (int) config('claims.max_evidences_per_claim', 5);

        return [
            'reason.required' => 'Debés seleccionar un motivo para el reclamo.',
            'reason.enum' => 'El motivo seleccionado no es válido.',
            'description.required' => 'Debés describir el problema ocurrido con tu compra.',
            'description.min' => 'La descripción debe tener al menos :min caracteres.',
            'description.max' => 'La descripción no puede superar los :max caracteres.',
            'evidences.array' => 'Las evidencias deben enviarse como una lista de imágenes.',
            'evidences.max' => "Podés adjuntar como máximo {$maxFiles} fotos de evidencia.",
            'evidences.*.image' => 'Cada archivo adjunto debe ser una imagen válida.',
            'evidences.*.mimes' => 'Las imágenes deben estar en formato JPG, JPEG, PNG o WEBP.',
            'evidences.*.max' => 'Cada imagen no puede superar los 5 MB.',
        ];
    }
}
