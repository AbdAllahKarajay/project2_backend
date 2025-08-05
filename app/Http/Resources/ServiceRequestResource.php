<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'scheduled_at' => $this->scheduled_at,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'special_instructions' => $this->special_instructions,
            'location' => $this->location,
        ];
    }
}