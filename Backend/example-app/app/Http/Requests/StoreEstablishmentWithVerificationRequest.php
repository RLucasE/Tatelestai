<?php

namespace App\Http\Requests;

use App\Enums\TrustScoreCriterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstablishmentWithVerificationRequest extends FormRequest
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
            'establishment_type_id' => 'required|integer|exists:establishment_types,id',

            // Modalidad A: Google Places
            'google_place_id' => 'nullable|string',

            // Modalidad B: Carga Manual con Mapa (requerido si no se envía google_place_id)
            'name' => 'required_without:google_place_id|nullable|string|max:255',
            'address' => 'required_without:google_place_id|nullable|string|max:255',
            'latitude' => 'required_without:google_place_id|nullable|numeric|between:-90,90',
            'longitude' => 'required_without:google_place_id|nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:50',

            // Archivos de verificación
            'verification_files' => 'required|array|min:1|max:6',
            'verification_files.*.file' => 'required|file|mimes:jpeg,png,pdf|max:5120', // 5MB max por archivo
            'verification_files.*.document_category' => ['nullable', 'string', Rule::in(TrustScoreCriterion::values())],
            'additional_info' => 'nullable|string|max:1000',
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
            'establishment_type_id.required' => 'El tipo de establecimiento es requerido',
            'establishment_type_id.exists' => 'El tipo de establecimiento seleccionado no existe',
            'name.required_without' => 'El nombre del establecimiento es requerido si no seleccionas un lugar de Google Places',
            'address.required_without' => 'La dirección es requerida si no seleccionas un lugar de Google Places',
            'latitude.required_without' => 'Debes ubicar la latitud en el mapa si no seleccionas un lugar de Google Places',
            'longitude.required_without' => 'Debes ubicar la longitud en el mapa si no seleccionas un lugar de Google Places',
            'verification_files.required' => 'Debes subir al menos un archivo de verificación',
            'verification_files.array' => 'Los archivos de verificación deben ser un arreglo',
            'verification_files.min' => 'Debes subir al menos un archivo de verificación',
            'verification_files.max' => 'No puedes subir más de 6 archivos de verificación',
            'verification_files.*.file.required' => 'Cada archivo de verificación es requerido',
            'verification_files.*.file.mimes' => 'Cada archivo debe ser JPG, PNG o PDF',
            'verification_files.*.file.max' => 'Cada archivo no debe superar los 5MB',
        ];
    }
}
