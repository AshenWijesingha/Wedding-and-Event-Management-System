<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the client's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->where('client_id', $request->user()->client_id ?? $request->user()->id)
            ->paginate($request->per_page ?? 15);

        return response()->json($bookings);
    }

    /**
     * Display the specified booking.
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        $clientId = $request->user()->client?->id ?? $request->user()->id;

        if ($booking->client_id !== $clientId) {
            abort(403, 'You are not authorized to view this booking.');
        }

        return response()->json($booking);
    }
}
