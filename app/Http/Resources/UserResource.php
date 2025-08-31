<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'wallet_balance' => $this->wallet_balance,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Include relationships when loaded
            'service_requests_count' => $this->whenCounted('serviceRequests'),
            'locations_count' => $this->whenCounted('locations'),
            'ratings_count' => $this->whenCounted('ratings'),
            'loyalty_points_total' => $this->when(isset($this->loyalty_points_total), $this->loyalty_points_total),
            
            // Include relationships when requested
            'service_requests' => ServiceRequestResource::collection($this->whenLoaded('serviceRequests')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),
            'ratings' => RatingResource::collection($this->whenLoaded('ratings')),
            'loyalty_points' => $this->whenLoaded('loyaltyPoints'),
        ];
    }
}
