<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequestStatus extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])
            ],
            'notes' => 'sometimes|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required.',
            'status.in' => 'The selected status is invalid.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
