<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->user()?->hasRole('Médico') ?? false;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['sometimes', 'integer', 'exists:patients,id'],
            'diagnosis' => ['sometimes', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'string', 'in:active,completed,cancelled'],
            'issued_at' => ['sometimes', 'date'],
            'expires_at' => ['nullable', 'date', 'after:issued_at'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.medication_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.dosage' => ['required_with:items', 'string', 'max:255'],
            'items.*.frequency' => ['required_with:items', 'string', 'max:255'],
            'items.*.duration' => ['required_with:items', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
