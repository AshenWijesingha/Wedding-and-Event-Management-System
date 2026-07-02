<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VenueResource;
use App\Models\Booking;
use App\Models\Venue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Venue::class, 'venue');
    }

    public function index(Request $request): Response
    {
        $venues = Venue::query()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status)
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Venues/Index', [
            'venues' => VenueResource::collection($venues),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Venues/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:venues',
            'description' => 'nullable|string',
            'capacity_min' => 'required|integer|min:1',
            'capacity_max' => 'required|integer|min:1|gte:capacity_min',
            'base_price' => 'required|numeric|min:0',
            'weekend_surcharge' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'status' => 'nullable|in:active,inactive,maintenance',
            'hotel_id' => ['nullable', Rule::exists('hotels', 'id')->where('tenant_id', auth()->user()->tenant_id)],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['approval_status'] = 'draft';

        $baseSlug = $validated['slug'];
        $i = 1;
        while (Venue::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$baseSlug}-{$i}";
            $i++;
        }

        try {
            Venue::create($validated);
        } catch (UniqueConstraintViolationException $e) {
            return back()->withErrors(['slug' => 'A venue with this slug already exists.'])->withInput();
        }

        return redirect()->route('admin.venues.index')->with('success', 'Venue created successfully.');
    }

    public function edit(Venue $venue): Response
    {
        return Inertia::render('Venues/Edit', [
            'venue' => new VenueResource($venue),
        ]);
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:venues,slug,' . $venue->id,
            'description' => 'nullable|string',
            'capacity_min' => 'nullable|integer|min:1',
            'capacity_max' => 'nullable|integer|min:1',
            'base_price' => 'nullable|numeric|min:0',
            'weekend_surcharge' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'status' => 'nullable|in:active,inactive,maintenance',
            'hotel_id' => ['nullable', Rule::exists('hotels', 'id')->where('tenant_id', auth()->user()->tenant_id)],
        ]);

        $venue->update($validated);

        return redirect()->route('admin.venues.index')->with('success', 'Venue updated successfully.');
    }

    public function submit(Venue $venue): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $venue);
        abort_unless(auth()->user()->can('venues.submit'), 403);
        $venue->submit(request()->user());

        return back()->with('success', 'Venue submitted for approval.');
    }

    public function availability(Venue $venue): Response
    {
        $bookings = Booking::where('venue_id', $venue->id)
            ->whereNotIn('status', ['cancelled'])
            ->with('client')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'booking_number' => $b->booking_number,
                'client_name' => $b->client?->first_name . ' ' . $b->client?->last_name,
                'event_date' => $b->event_date?->toDateString(),
                'event_type' => $b->event_type,
                'guest_count' => $b->guest_count,
                'status' => $b->status,
            ]);

        return Inertia::render('Venues/Availability', [
            'venue' => new VenueResource($venue),
            'events' => $bookings,
        ]);
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if ($venue->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return back()->with('error', 'Cannot delete venue with active bookings.');
        }

        $venue->delete();

        return redirect()->route('admin.venues.index')->with('success', 'Venue deleted.');
    }
}
