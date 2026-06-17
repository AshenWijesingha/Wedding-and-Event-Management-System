<?php

namespace App\Services;

use App\Models\Venue;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    /**
     * Check if a venue is available on a specific date.
     */
    public function isVenueAvailable(int $venueId, string $date, ?int $ignoreBookingId = null): bool
    {
        return !Booking::where('venue_id', $venueId)
            ->whereDate('event_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->when($ignoreBookingId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();
    }

    /**
     * Get availability calendar for a venue.
     */
    public function getAvailabilityCalendar(int $venueId, string $startDate, string $endDate): array
    {
        $bookings = Booking::where('venue_id', $venueId)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->get(['event_date', 'status', 'event_type']);

        $calendar = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $booking = $bookings->firstWhere('event_date', $dateStr);
            
            $calendar[] = [
                'date' => $dateStr,
                'status' => $booking ? $this->mapStatus($booking->status) : 'available',
                'event_type' => $booking ? $booking->event_type : null,
            ];
            
            $current->addDay();
        }

        return $calendar;
    }

    /**
     * Get availability for multiple venues on a date range.
     */
    public function getMultiVenueAvailability(array $venueIds, string $startDate, string $endDate): array
    {
        $bookings = Booking::whereIn('venue_id', $venueIds)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->get(['venue_id', 'event_date', 'status']);

        $availability = [];
        
        foreach ($venueIds as $venueId) {
            $availability[$venueId] = [];
            $current = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                $booking = $bookings->first(function ($b) use ($venueId, $dateStr) {
                    return $b->venue_id == $venueId && $b->event_date?->format('Y-m-d') === $dateStr;
                });
                
                $availability[$venueId][$dateStr] = $booking ? $this->mapStatus($booking->status) : 'available';
                
                $current->addDay();
            }
        }

        return $availability;
    }

    /**
     * Reserve a venue for a specific date with locking.
     */
    public function reserveVenue(int $venueId, string $date): bool
    {
        return DB::transaction(function () use ($venueId, $date) {
            $exists = Booking::where('venue_id', $venueId)
                ->where('event_date', $date)
                ->whereNotIn('status', ['cancelled'])
                ->lockForUpdate()
                ->exists();

            return !$exists;
        });
    }

    /**
     * Get next available date for a venue.
     */
    public function getNextAvailableDate(int $venueId, ?string $fromDate = null): ?string
    {
        $from = $fromDate ? Carbon::parse($fromDate) : Carbon::today();
        $maxDays = config('eventpro.defaults.booking.max_advance_days', 365);
        $end = Carbon::today()->addDays($maxDays);

        $bookedDates = Booking::where('venue_id', $venueId)
            ->whereBetween('event_date', [$from->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('status', ['cancelled'])
            ->pluck('event_date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->toArray();

        $current = $from->copy();
        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            if (!in_array($dateStr, $bookedDates)) {
                return $dateStr;
            }
            $current->addDay();
        }

        return null;
    }

    /**
     * Map booking status to availability status.
     */
    private function mapStatus(string $status): string
    {
        return match($status) {
            'pending', 'tentative' => 'tentative',
            'confirmed', 'completed' => 'booked',
            default => 'available',
        };
    }
}
