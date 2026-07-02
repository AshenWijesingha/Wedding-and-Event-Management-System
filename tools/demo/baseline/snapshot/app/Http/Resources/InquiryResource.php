<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inquiry_number' => $this->inquiry_number,
            'status' => $this->status,
            'event_type' => $this->event_type,
            'preferred_date' => $this->preferred_date?->toDateString(),
            'alternate_date' => $this->alternate_date?->toDateString(),
            'guest_count' => $this->guest_count,
            'budget' => [
                'min' => $this->budget_range_min ? (float) $this->budget_range_min : null,
                'max' => $this->budget_range_max ? (float) $this->budget_range_max : null,
            ],
            'message' => $this->message,
            'source' => $this->source,
            'client' => new ClientResource($this->whenLoaded('client')),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'package' => new PackageResource($this->whenLoaded('package')),
            'assigned_to' => $this->assigned_to,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
