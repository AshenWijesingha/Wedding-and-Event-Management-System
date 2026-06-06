<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quotation_number' => $this->quotation_number,
            'status' => $this->status,
            'event_date' => $this->event_date?->toDateString(),
            'guest_count' => $this->guest_count,
            'items' => $this->items ?? [],
            'financial' => [
                'subtotal' => (float) $this->subtotal,
                'discount' => (float) $this->discount_amount,
                'tax' => (float) $this->tax_amount,
                'total' => (float) $this->total_amount,
            ],
            'valid_until' => $this->valid_until?->toDateString(),
            'client' => new ClientResource($this->whenLoaded('client')),
            'venue' => new VenueResource($this->whenLoaded('venue')),
            'notes' => $this->notes,
            'terms_and_conditions' => $this->terms_and_conditions,
            'sent_at' => $this->sent_at?->toDateTimeString(),
            'viewed_at' => $this->viewed_at?->toDateTimeString(),
            'accepted_at' => $this->accepted_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
