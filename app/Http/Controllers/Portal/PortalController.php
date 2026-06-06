<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\QuotationResource;
use App\Models\Booking;
use App\Models\Notification as NotificationModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    private function getClientId(): ?int
    {
        $user = Auth::user();
        return $user->client?->id;
    }

    public function dashboard(): Response
    {
        $clientId = $this->getClientId();

        $recentBookings = Booking::with(['venue', 'package'])
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $upcomingEvents = Booking::with(['venue'])
            ->where('client_id', $clientId)
            ->upcoming()
            ->whereIn('status', ['confirmed', 'tentative'])
            ->limit(3)
            ->get();

        $financialSummary = [
            'total_booked'   => (float) Booking::where('client_id', $clientId)->sum('total_amount'),
            'total_paid'     => (float) Booking::where('client_id', $clientId)->sum('paid_amount'),
            'total_balance'  => (float) Booking::where('client_id', $clientId)->sum('balance_amount'),
        ];

        $unreadNotifications = Auth::user()->unreadNotifications()->limit(5)->get();

        return Inertia::render('Portal/Dashboard', [
            'recentBookings'      => BookingResource::collection($recentBookings),
            'upcomingEvents'      => BookingResource::collection($upcomingEvents),
            'financialSummary'    => $financialSummary,
            'unreadNotifications' => $unreadNotifications->map(fn ($n) => [
                'id'   => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    public function bookings(Request $request): Response
    {
        $clientId = $this->getClientId();

        $bookings = Booking::with(['venue', 'package', 'payments'])
            ->where('client_id', $clientId)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('event_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Portal/Bookings', [
            'bookings' => BookingResource::collection($bookings),
            'filters'  => $request->only(['status']),
        ]);
    }

    public function bookingShow(Booking $booking): Response
    {
        $clientId = $this->getClientId();
        abort_if($booking->client_id !== $clientId, 403);

        return Inertia::render('Portal/BookingShow', [
            'booking' => new BookingResource($booking->load(['venue', 'package', 'payments', 'vendors'])),
        ]);
    }

    public function quotations(): Response
    {
        $clientId = $this->getClientId();

        $quotations = \App\Models\Quotation::with(['venue'])
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Portal/Quotations', [
            'quotations' => QuotationResource::collection($quotations),
        ]);
    }

    public function payments(): Response
    {
        $clientId = $this->getClientId();

        $payments = \App\Models\Payment::with(['booking'])
            ->where('client_id', $clientId)
            ->orderByDesc('payment_date')
            ->paginate(10);

        $summary = [
            'total_paid'    => (float) \App\Models\Payment::where('client_id', $clientId)->completed()->sum('amount'),
            'total_pending' => (float) \App\Models\Payment::where('client_id', $clientId)->where('status', 'pending')->sum('amount'),
        ];

        return Inertia::render('Portal/Payments', [
            'payments' => PaymentResource::collection($payments),
            'summary'  => $summary,
        ]);
    }

    public function markNotificationRead(Request $request): RedirectResponse
    {
        Auth::user()->notifications()->where('id', $request->id)->update(['read_at' => now()]);
        return back();
    }
}
