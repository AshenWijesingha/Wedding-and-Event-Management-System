<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['client', 'venue'])
            ->paginate($request->per_page ?? 15);
        return response()->json($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'venue_id' => 'required|exists:venues,id',
            'event_date' => 'required|date',
            'event_type' => 'required|string',
            'guest_count' => 'required|integer|min:1',
            'status' => 'sometimes|string|in:tentative,confirmed,completed,cancelled',
        ]);

        $booking = Booking::create($validated);
        return response()->json($booking, 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load(['client', 'venue', 'payments']));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'event_date' => 'sometimes|date',
            'guest_count' => 'sometimes|integer|min:1',
            'status' => 'sometimes|string|in:tentative,confirmed,completed,cancelled',
        ]);

        $booking->update($validated);
        return response()->json($booking);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->delete();
        return response()->json(null, 204);
    }
}
