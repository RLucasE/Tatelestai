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
            'establishment_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
            'owner_selfie' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
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
            'establishment_photo.required' => 'La foto del establecimiento es requerida',
            'establishment_photo.image' => 'El archivo debe ser una imagen',
            'establishment_photo.max' => 'La foto del establecimiento no debe superar los 5MB',
            'owner_selfie.required' => 'La selfie del propietario es requerida',
            'owner_selfie.image' => 'El archivo debe ser una imagen',
            'owner_selfie.max' => 'La selfie no debe superar los 5MB',
        ];
    }
}

