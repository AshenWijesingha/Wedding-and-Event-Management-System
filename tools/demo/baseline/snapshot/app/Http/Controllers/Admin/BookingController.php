<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\Vendor;
use App\Models\Venue;
use App\Notifications\StaffNotification;
use App\Services\AvailabilityService;
use App\Support\Notifier;
use App\Support\TenantRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $bookings = Booking::with(['client', 'venue', 'package'])
            ->when($request->search, fn ($q, $search) => $q->where('booking_number', 'like', "%{$search}%")
                ->orWhereHas('client', fn ($cq) => $cq->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                )
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status)
            )
            ->orderBy('event_date', 'desc')
            ->paginate(min((int) ($request->per_page ?? 15), 100))
            ->withQueryString();

        return Inertia::render('Bookings/Index', [
            'bookings' => BookingResource::collection($bookings),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        // Prefill from an inquiry when converting (inquiry -> booking): carry every
        // known detail forward so staff only fill the gaps (times, deposit, etc.).
        $prefill = [];
        if ($request->filled('inquiry_id')) {
            $inquiry = Inquiry::find($request->integer('inquiry_id'));
            if ($inquiry) {
                $venue = $inquiry->venue_id ? Venue::find($inquiry->venue_id) : null;
                $package = $inquiry->package_id ? Package::find($inquiry->package_id) : null;
                $total = (float) ($venue->base_price ?? 0) + (float) ($package->base_price ?? 0);

                $prefill = [
                    'inquiry_id' => $inquiry->id,
                    'client_id' => $inquiry->client_id,
                    'venue_id' => $inquiry->venue_id,
                    'package_id' => $inquiry->package_id,
                    'event_type' => $inquiry->event_type,
                    'event_date' => optional($inquiry->preferred_date)->toDateString(),
                    'guest_count' => $inquiry->guest_count,
                    'notes' => $inquiry->message,
                    'total_amount' => $total > 0 ? $total : null,
                ];
            }
        }

        return Inertia::render('Bookings/Create', [
            'venues' => Venue::where('status', 'active')->approved()->orderBy('name')->get(['id', 'name', 'base_price']),
            'packages' => Package::where('status', 'active')->approved()->orderBy('name')->get(['id', 'name', 'base_price']),
            'clients' => Client::orderBy('first_name')->get()->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim("{$c->first_name} {$c->last_name}"),
            ]),
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request, AvailabilityService $availability): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);

        $validated = $request->validate([
            'client_id' => ['required', TenantRule::exists('clients')],
            'venue_id' => ['required', TenantRule::exists('venues')],
            'package_id' => ['nullable', TenantRule::exists('packages')],
            'event_type' => 'required|string|max:50',
            'event_date' => 'required|date|after_or_equal:today',
            'setup_time' => 'nullable|date_format:H:i',
            'event_start_time' => 'nullable|date_format:H:i',
            'event_end_time' => 'nullable|date_format:H:i',
            'guest_count' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,tentative,confirmed',
            'notes' => 'nullable|string',
        ]);

        if (! $availability->isVenueAvailable($validated['venue_id'], $validated['event_date'])) {
            return back()->withErrors(['event_date' => 'This venue is already booked on the selected date.'])->withInput();
        }

        $paid = (float) ($validated['paid_amount'] ?? 0);

        Booking::create([
            ...$validated,
            'booking_number' => Booking::generateBookingNumber(),
            'paid_amount' => $paid,
            'balance_amount' => (float) $validated['total_amount'] - $paid,
            'status' => $validated['status'] ?? 'tentative',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.bookings.index')->with('success', 'Booking created successfully.');
    }

    public function edit(Booking $booking): Response
    {
        return Inertia::render('Bookings/Edit', [
            'booking' => new BookingResource($booking->load(['client', 'venue', 'package'])),
            'venues' => Venue::where('status', 'active')->approved()->orderBy('name')->get(['id', 'name', 'base_price']),
            'packages' => Package::where('status', 'active')->approved()->orderBy('name')->get(['id', 'name', 'base_price']),
            'clients' => Client::orderBy('first_name')->get()->map(fn ($c) => [
                'id' => $c->id,
                'name' => trim("{$c->first_name} {$c->last_name}"),
            ]),
        ]);
    }

    public function update(Request $request, Booking $booking, AvailabilityService $availability): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);

        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'Completed or cancelled bookings cannot be edited.');
        }

        $validated = $request->validate([
            'client_id' => ['required', TenantRule::exists('clients')],
            'venue_id' => ['required', TenantRule::exists('venues')],
            'package_id' => ['nullable', TenantRule::exists('packages')],
            'event_type' => 'required|string|max:50',
            'event_date' => 'required|date|after_or_equal:today',
            'setup_time' => 'nullable|date_format:H:i',
            'event_start_time' => 'nullable|date_format:H:i',
            'event_end_time' => 'nullable|date_format:H:i',
            'guest_count' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,tentative,confirmed',
            'notes' => 'nullable|string',
        ]);

        // Re-check availability but exclude THIS booking, so saving the same date
        // is not a false self-conflict.
        if (! $availability->isVenueAvailable($validated['venue_id'], $validated['event_date'], $booking->id)) {
            return back()->withErrors(['event_date' => 'This venue is already booked on the selected date.'])->withInput();
        }

        $paid = (float) ($validated['paid_amount'] ?? $booking->paid_amount);

        $booking->update([
            ...$validated,
            'paid_amount' => $paid,
            'balance_amount' => (float) $validated['total_amount'] - $paid,
            'status' => $validated['status'] ?? $booking->status,
        ]);

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Booking updated successfully.');
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
        abort_unless(request()->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);
        if (! in_array($booking->status, ['pending', 'tentative'])) {
            return back()->with('error', 'Only pending or tentative bookings can be confirmed.');
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'confirmed']);
        });

        Notifier::mail(
            optional($booking->client)->email,
            new BookingConfirmedMail($booking),
        );
        Notifier::staff(
            Tenant::current(),
            new StaffNotification(
                'booking_confirmed',
                "Booking {$booking->booking_number} was confirmed.",
                "/admin/bookings/{$booking->id}",
            ),
        );

        return back()->with('success', 'Booking confirmed successfully.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);
        if (! $booking->canBeCancelled()) {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $booking->update([
            'status' => 'cancelled',
            'notes' => $booking->notes
                ? $booking->notes . "\n\nCancellation reason: " . ($request->cancellation_reason ?? 'Not specified')
                : 'Cancellation reason: ' . ($request->cancellation_reason ?? 'Not specified'),
        ]);

        return back()->with('success', 'Booking cancelled.');
    }

    public function attachVendor(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);
        $data = $request->validate([
            'vendor_id' => ['required', TenantRule::exists('vendors')],
            'service_description' => 'nullable|string|max:500',
            'agreed_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $booking->vendors()->syncWithoutDetaching([
            $data['vendor_id'] => [
                'service_description' => $data['service_description'] ?? null,
                'agreed_amount' => $data['agreed_amount'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'confirmed',
            ],
        ]);

        return back()->with('success', 'Vendor assigned to booking.');
    }

    public function detachVendor(Booking $booking, Vendor $vendor): RedirectResponse
    {
        abort_unless(request()->user()->hasAnyRole(['super_admin', 'tenant_owner', 'admin', 'manager']), 403);
        $booking->vendors()->detach($vendor->id);

        return back()->with('success', 'Vendor removed from booking.');
    }
}
