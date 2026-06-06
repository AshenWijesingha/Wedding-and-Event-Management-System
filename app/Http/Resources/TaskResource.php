<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'priority'     => $this->priority,
            'status'       => $this->status,
            'due_date'     => $this->due_date?->toDateString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'booking_id'   => $this->booking_id,
            'booking'      => $this->whenLoaded('booking', fn () => [
                'id'             => $this->booking->id,
                'booking_number' => $this->booking->booking_number,
            ]),
            'assignee'     => new StaffResource($this->whenLoaded('assignee')),
            'created_at'   => $this->created_at?->toDateTimeString(),
        ];
    }
}
