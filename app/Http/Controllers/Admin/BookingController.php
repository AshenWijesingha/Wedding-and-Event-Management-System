<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
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
            'booking' => new BookingResource($booking->load(['client', 'venue', 'package', 'payments', 'vendors'])),
            'availableVendors' => Vendor::active()->orderBy('name')->get(['id', 'name', 'category']),
        ]);
    }

    public function confirm(Booking $booking): RedirectResponse
    {
        if (!in_array($booking->status, ['tentative'])) {
            return back()->with('error', 'Only tentative bookings can be confirmed.');
        }

        $booking->update(['status' => 'confirmed']);

        return back()->with('success', 'Booking confirmed successfully.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        if (!$booking->canBeCancelled()) {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $booking->update([
            'status' => 'cancelled',
            'notes' => $booking->notes
                ? $booking->notes . "\n\nCancellation reason: " . ($request->cancellation_reason ?? 'Not specified')
                : "Cancellation reason: " . ($request->cancellation_reason ?? 'Not specified'),
        ]);

        return back()->with('success', 'Booking cancelled.');
    }

    public function attachVendor(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id'           => 'required|exists:vendors,id',
            'service_description' => 'nullable|string|max:500',
            'agreed_amount'       => 'nullable|numeric|min:0',
            'notes'               => 'nullable|string|max:500',
        ]);

        $booking->vendors()->syncWithoutDetaching([
            $data['vendor_id'] => [
                'service_description' => $data['service_description'] ?? null,
                'agreed_amount'       => $data['agreed_amount'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'status'              => 'confirmed',
            ],
        ]);

        return back()->with('success', 'Vendor assigned to booking.');
    }

    public function detachVendor(Booking $booking, Vendor $vendor): RedirectResponse
    {
        $booking->vendors()->detach($vendor->id);

        return back()->with('success', 'Vendor removed from booking.');
    }
}
