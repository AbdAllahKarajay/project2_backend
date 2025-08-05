<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
        ];
    }
}