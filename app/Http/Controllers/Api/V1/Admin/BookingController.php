<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['client', 'venue', 'package'])
            ->when($request->status, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->when($request->event_type, fn ($q, $type) =>
                $q->where('event_type', $type)
            )
            ->when($request->client_id, fn ($q, $clientId) =>
                $q->where('client_id', $clientId)
            )
            ->when($request->venue_id, fn ($q, $venueId) =>
                $q->where('venue_id', $venueId)
            )
            ->when($request->date_from, fn ($q, $date) =>
                $q->where('event_date', '>=', $date)
            )
            ->when($request->date_to, fn ($q, $date) =>
                $q->where('event_date', '<=', $date)
            )
            ->when($request->search, fn ($q, $search) =>
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($cq) =>
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                  )
            )
            ->orderBy(in_array($request->sort_by, ['event_date', 'created_at', 'total_amount', 'status', 'guest_count']) ? $request->sort_by : 'event_date', $request->sort_dir === 'asc' ? 'asc' : 'desc')
            ->paginate(min((int) ($request->per_page ?? 15), 100));

        return $this->success(BookingResource::collection($bookings));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'venue_id' => 'required|exists:venues,id',
            'package_id' => 'nullable|exists:packages,id',
            'event_type' => 'required|string|max:100',
            'event_date' => 'required|date|after_or_equal:today',
            'setup_time' => 'nullable|date_format:H:i',
            'event_start_time' => 'nullable|date_format:H:i',
            'event_end_time' => 'nullable|date_format:H:i',
            'guest_count' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['booking_number'] = Booking::generateBookingNumber();
        $validated['status'] = 'tentative';
        $validated['paid_amount'] = 0;
        $validated['balance_amount'] = $validated['total_amount'];

        $booking = Booking::create($validated);

        return $this->created(new BookingResource($booking->load(['client', 'venue', 'package'])));
    }

    public function show(Booking $booking): JsonResponse
    {
        return $this->success(new BookingResource($booking->load(['client', 'venue', 'package', 'payments'])));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'sometimes|string|max:100',
            'event_date' => 'sometimes|date',
            'setup_time' => 'nullable|date_format:H:i',
            'event_start_time' => 'nullable|date_format:H:i',
            'event_end_time' => 'nullable|date_format:H:i',
            'guest_count' => 'sometimes|integer|min:1',
            'total_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:tentative,confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['total_amount'])) {
            $validated['balance_amount'] = $validated['total_amount'] - $booking->paid_amount;
        }

        $booking->update($validated);

        return $this->success(new BookingResource($booking->fresh(['client', 'venue', 'package', 'payments'])));
    }

    public function destroy(Booking $booking): JsonResponse
    {
        if (!$booking->canBeCancelled()) {
            return $this->error('Completed bookings cannot be deleted.', 422);
        }

        $booking->delete();

        return $this->noContent();
    }
}
