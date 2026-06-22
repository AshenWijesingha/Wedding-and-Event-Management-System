<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'staff']) ?? false;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'venue_id' => ['required', 'exists:venues,id'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'event_type' => ['required', 'string', 'max:100'],
            'event_date' => ['required', 'date', 'after:today'],
            'setup_time' => ['nullable', 'date_format:H:i'],
            'event_start_time' => ['nullable', 'date_format:H:i'],
            'event_end_time' => ['nullable', 'date_format:H:i', 'after:event_start_time'],
            'guest_count' => ['required', 'integer', 'min:1'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->checkVenueAvailability($validator);
            $this->checkGuestCapacity($validator);
        });
    }

    private function checkVenueAvailability($validator): void
    {
        if (! $this->filled(['venue_id', 'event_date'])) {
            return;
        }

        $conflict = Booking::where('venue_id', $this->venue_id)
            ->where('event_date', $this->event_date)
            ->whereNotIn('status', ['cancelled'])
            ->when($this->route('booking'), fn ($q) => $q->where('id', '!=', $this->route('booking')->id)
            )
            ->exists();

        if ($conflict) {
            $validator->errors()->add(
                'event_date',
                'This venue is already booked on the selected date.'
            );
        }
    }

    private function checkGuestCapacity($validator): void
    {
        if (! $this->filled(['venue_id', 'guest_count'])) {
            return;
        }

        $venue = Venue::find($this->venue_id);
        if (! $venue) {
            return;
        }

        if ($this->guest_count < $venue->capacity_min) {
            $validator->errors()->add(
                'guest_count',
                "Guest count must be at least {$venue->capacity_min} for this venue."
            );
        }

        if ($this->guest_count > $venue->capacity_max) {
            $validator->errors()->add(
                'guest_count',
                "Guest count exceeds venue maximum capacity of {$venue->capacity_max}."
            );
        }
    }

    public function messages(): array
    {
        return [
            'event_date.after' => 'The event date must be a future date.',
            'event_end_time.after' => 'The event end time must be after the start time.',
        ];
    }
}
