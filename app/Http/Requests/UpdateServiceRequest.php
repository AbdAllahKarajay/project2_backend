<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => 'sometimes|exists:locations,id',
            'scheduled_at' => 'sometimes|date|after:now',
            'special_instructions' => 'sometimes|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.exists' => 'The selected location is invalid.',
            'scheduled_at.after' => 'The scheduled time must be in the future.',
            'special_instructions.max' => 'Special instructions cannot exceed 1000 characters.',
        ];
    }
}
