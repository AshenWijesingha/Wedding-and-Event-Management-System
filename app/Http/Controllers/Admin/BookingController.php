<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $bookings = Booking::with(['client', 'venue', 'package'])
            ->when($request->search, fn ($q, $search) =>
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($cq) =>
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                  )
            )
            ->when($request->status, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->orderBy('event_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Bookings/Index', [
            'bookings' => BookingResource::collection($bookings),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Booking $booking): Response
    {
        return Inertia::render('Bookings/Show', [
            'booking' => new BookingResource($booking->load(['client', 'venue', 'package', 'payments'])),
        ]);
    }
}
