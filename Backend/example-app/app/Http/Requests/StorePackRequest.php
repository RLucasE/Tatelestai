<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackRequest extends FormRequest
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
            'pack_template_id' => 'nullable|integer|exists:pack_templates,id',
            'title' => 'required_without:pack_template_id|nullable|string|max:100',
            'description' => 'required_without:pack_template_id|nullable|string|max:1000',
            'allergens' => 'nullable|array',
            'allergens.*' => 'string|max:50',
            'estimated_weight_kg' => 'nullable|numeric|min:0.01|max:100',
            'price' => 'required|numeric|min:1',
            'minimum_value' => 'required|numeric|min:1|gte:price',
            'quantity' => 'required|integer|min:1',
            'pickup_start_datetime' => 'required|date|after_or_equal:now',
            'pickup_end_datetime' => 'required|date|after:pickup_start_datetime',
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
            'pack_template_id.exists' => 'La plantilla de pack seleccionada no existe',
            'title.required_without' => 'El título es obligatorio si no seleccionas una plantilla',
            'description.required_without' => 'La descripción es obligatoria si no seleccionas una plantilla',
            'price.required' => 'El precio del pack es requerido',
            'minimum_value.required' => 'El valor mínimo garantizado es requerido',
            'minimum_value.gte' => 'El valor mínimo garantizado debe ser mayor o igual al precio del pack',
            'quantity.required' => 'La cantidad de cupos es requerida',
            'pickup_start_datetime.after_or_equal' => 'El inicio de la ventana de retiro no puede ser en el pasado',
            'pickup_end_datetime.after' => 'El fin de la ventana de retiro debe ser posterior al inicio',
        ];
    }
}
