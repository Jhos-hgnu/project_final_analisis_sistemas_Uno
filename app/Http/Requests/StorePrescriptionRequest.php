<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->user()?->hasRole('Médico') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'diagnosis' => ['required', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:issued_at'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['required', 'string', 'max:255'],
            'items.*.frequency' => ['required', 'string', 'max:255'],
            'items.*.duration' => ['required', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Debe seleccionar un paciente.',
            'patient_id.exists' => 'El paciente seleccionado no existe.',
            'diagnosis.required' => 'El diagnóstico es obligatorio.',
            'items.required' => 'Debe agregar al menos un medicamento.',
            'items.min' => 'Debe agregar al menos un medicamento.',
            'items.*.medication_name.required' => 'El nombre del medicamento es obligatorio.',
            'items.*.dosage.required' => 'La dosis es obligatoria.',
            'items.*.frequency.required' => 'La frecuencia es obligatoria.',
            'items.*.duration.required' => 'La duración es obligatoria.',
        ];
    }
}
