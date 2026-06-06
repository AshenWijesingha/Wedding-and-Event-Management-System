<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'capacity' => [
                'min' => $this->capacity_min,
                'max' => $this->capacity_max,
            ],
            'pricing' => [
                'base_price' => (float) $this->base_price,
                'weekend_surcharge' => (float) $this->weekend_surcharge,
            ],
            'amenities' => $this->amenities ?? [],
            'images' => $this->images ?? [],
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
