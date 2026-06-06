<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'status' => $this->status,
            'event' => [
                'type' => $this->event_type,
                'date' => $this->event_date?->toDateString(),
                'setup_time' => $this->setup_time,
                'start_time' => $this->event_start_time,
                'end_time' => $this->event_end_time,
                'guest_count' => $this->guest_count,
            ],
            'financial' => [
                'total' => (float) $this->total_amount,
                'paid' => (float) $this->paid_amount,
                'balance' => (float) $this->balance_amount,
            ],
            'client' => new ClientResource($this->whenLoaded('client')),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'package' => new PackageResource($this->whenLoaded('package')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
