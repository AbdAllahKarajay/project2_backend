<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'min_spend' => $this->min_spend,
            'expiry_date' => $this->expiry_date,
            'usage_limit' => $this->usage_limit,
        ];
    }
}