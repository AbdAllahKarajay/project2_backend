<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'base_price' => $this->base_price,
            'duration_minutes' => $this->duration_minutes,
            'average_rating' => $this->average_rating,
        ];
    }
}