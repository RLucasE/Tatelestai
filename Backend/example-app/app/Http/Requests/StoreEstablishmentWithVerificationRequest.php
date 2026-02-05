<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'google_place_id' => 'required|string',
            'establishment_type_id' => 'required|integer|exists:establishment_types,id',
            'verification_files' => 'required|array|min:1|max:6',
            'verification_files.*.file' => 'required|file|mimes:jpeg,png,pdf|max:5120', // 5MB max por archivo
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
            'google_place_id.required' => 'Debes seleccionar un lugar de Google Places',
            'establishment_type_id.required' => 'El tipo de establecimiento es requerido',
            'establishment_type_id.exists' => 'El tipo de establecimiento seleccionado no existe',
            'verification_files.required' => 'Debes subir al menos un archivo de verificación',
            'verification_files.array' => 'Los archivos de verificación deben ser un arreglo',
            'verification_files.min' => 'Debes subir al menos un archivo de verificación',
            'verification_files.max' => 'No puedes subir más de 6 archivos de verificación',
             'verification_files.*.file' => 'Cada archivo de verificación es requerido',
             'verification_files.*.file.mimes' => 'Cada archivo debe ser JPG, PNG o PDF',
             'verification_files.*.file.max' => 'Cada archivo no debe superar los 5MB',
        ];
    }
}

