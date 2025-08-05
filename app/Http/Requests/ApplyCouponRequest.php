<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest {
    public function authorize() {
        return true;
    }

    public function rules() {
        return [
            'code' => 'required|string|exists:coupons,code',
            'service_request_id' => 'required|exists:service_requests,id'
        ];
    }
}