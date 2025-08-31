<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:1.00',
                'max:10000.00', // Maximum top-up limit
            ],
            'payment_method' => [
                'required',
                'in:card,bank_transfer,cash',
            ],
            'reference' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Top-up amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Minimum top-up amount is $1.00.',
            'amount.max' => 'Maximum top-up amount is $10,000.00.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'reference.max' => 'Reference cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
        ];
    }
}
