<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'city' => $this->city,
            'address' => $this->address,
            'description' => $this->description,
            'star_rating' => $this->star_rating,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'changes_pending_review' => $this->changes_pending_review,
            'review_notes' => $this->review_notes,
            'venues_count' => $this->whenCounted('venues'),
            'packages_count' => $this->whenCounted('packages'),
        ];
    }
}
