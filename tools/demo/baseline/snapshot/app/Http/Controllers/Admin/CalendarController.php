<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(): Response
    {
        $events = Booking::with('client')
            ->whereNotNull('event_date')
            ->orderBy('event_date')
            ->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'title' => trim(($b->client?->full_name ?: 'Booking') . ' · ' . ucfirst((string) $b->event_type)),
                'start' => $b->event_date?->toDateString(),
                'url' => route('admin.bookings.show', $b),
                'status' => $b->status,
                'className' => 'fc-status-' . $b->status,
            ]);

        return Inertia::render('Calendar/Index', [
            'events' => $events,
        ]);
    }
}
