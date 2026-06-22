<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\QuotationResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Services\PayHereService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    private function getClientId(): int
    {
        $user = Auth::user();

        // Resilience: any `client` account missing its profile (e.g. created before the
        // profile was linked at registration) gets one created on first portal visit,
        // so the portal never dead-ends on a 403.
        $client = $user->client;
        if ($client === null) {
            $parts = preg_split('/\s+/', trim($user->name ?? ''), 2);
            $client = $user->client()->create([
                'tenant_id' => $user->tenant_id ?? optional(Tenant::current())->id,
                'first_name' => $parts[0] ?? ($user->name ?? 'Guest'),
                'last_name' => $parts[1] ?? '',
                'email' => $user->email,
            ]);
        }

        return $client->id;
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
            'total_booked' => (float) Booking::where('client_id', $clientId)->sum('total_amount'),
            'total_paid' => (float) Booking::where('client_id', $clientId)->sum('paid_amount'),
            'total_balance' => (float) Booking::where('client_id', $clientId)->sum('balance_amount'),
        ];

        $unreadNotifications = Auth::user()->unreadNotifications()->limit(5)->get();

        return Inertia::render('Portal/Dashboard', [
            'recentBookings' => BookingResource::collection($recentBookings),
            'upcomingEvents' => BookingResource::collection($upcomingEvents),
            'financialSummary' => $financialSummary,
            'unreadNotifications' => $unreadNotifications->map(fn ($n) => [
                'id' => $n->id,
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
            'filters' => $request->only(['status']),
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

        $quotations = Quotation::with(['venue'])
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

        $payments = Payment::with(['booking'])
            ->where('client_id', $clientId)
            ->orderByDesc('payment_date')
            ->paginate(10);

        $summary = [
            'total_paid' => (float) Payment::where('client_id', $clientId)->completed()->sum('amount'),
            'total_pending' => (float) Payment::where('client_id', $clientId)->where('status', 'pending')->sum('amount'),
        ];

        // Bookings still owing money — the "Pay now" targets. Hidden entirely when
        // the tenant has no PayHere credentials configured.
        $tenant = Tenant::current();
        $payhereEnabled = $tenant ? app(PayHereService::class)->isConfigured($tenant) : false;

        $payable = Booking::where('client_id', $clientId)
            ->where('balance_amount', '>', 0)
            ->orderByDesc('event_date')
            ->get(['id', 'booking_number', 'event_date', 'total_amount', 'paid_amount', 'balance_amount'])
            ->map(fn ($b) => [
                'id' => $b->id,
                'booking_number' => $b->booking_number,
                'event_date' => $b->event_date?->toDateString(),
                'total_amount' => (float) $b->total_amount,
                'paid_amount' => (float) $b->paid_amount,
                'balance_amount' => (float) $b->balance_amount,
            ]);

        return Inertia::render('Portal/Payments', [
            'payments' => PaymentResource::collection($payments),
            'summary' => $summary,
            'payableBookings' => $payable,
            'payhereEnabled' => $payhereEnabled,
        ]);
    }

    public function markNotificationRead(Request $request): RedirectResponse
    {
        Auth::user()->notifications()->where('id', $request->id)->update(['read_at' => now()]);

        return back();
    }
}
