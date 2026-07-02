<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(Request $request)
    {
        $venues = Venue::active()
            ->approved()
            ->where(fn ($q) => $q->whereNull('hotel_id')->orWhereHas('hotel', fn ($h) => $h->approved()))
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            )
            ->when($request->capacity, fn ($q, $cap) => $q->where('capacity_max', '>=', $cap)
            )
            ->when($request->max_price, fn ($q, $price) => $q->where('base_price', '<=', $price)
            )
            ->orderBy('name')
            ->paginate(9);

        return view('venues.index', compact('venues'));
    }

    public function show(Venue $venue)
    {
        if ($venue->status !== 'active' || ! $venue->isApproved()) {
            abort(404);
        }

        // If venue belongs to a hotel, that hotel must also be approved.
        if ($venue->hotel_id !== null && (! $venue->hotel || ! $venue->hotel->isApproved())) {
            abort(404);
        }

        $unavailableDates = $venue->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->pluck('event_date')
            ->map(fn ($d) => $d->toDateString())
            ->values()
            ->toArray();

        return view('venues.show', compact('venue', 'unavailableDates'));
    }
}
