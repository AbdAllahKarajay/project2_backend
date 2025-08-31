<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedeemLoyaltyRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loyalty_reward_id' => [
                'required',
                'exists:loyalty_rewards,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'loyalty_reward_id.required' => 'Loyalty reward ID is required.',
            'loyalty_reward_id.exists' => 'The selected loyalty reward is invalid.',
        ];
    }
}
