<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest {
    public function authorize() {
        return true;
    }

    public function rules() {
        return [
            'service_id' => 'required|exists:services,id',
            'location_id' => 'exists:locations,id|nullable',
            'location' => 'string|nullable',
            'scheduled_at' => 'required|date|after:now',
            'special_instructions' => 'nullable|string',
        ];
    }
}