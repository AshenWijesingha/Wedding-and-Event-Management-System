<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', 0);

        $revenueByMonth = Payment::completed()
            ->whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $months = collect(range(1, 12))->mapWithKeys(fn ($m) => [
            $m => [
                'label'   => date('M', mktime(0, 0, 0, $m, 1)),
                'revenue' => (float) ($revenueByMonth[$m] ?? 0),
            ],
        ]);

        $bookingsByStatus = Booking::whereYear('event_date', $year)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $topVenues = Booking::selectRaw('bookings.venue_id, venues.name as venue_name, COUNT(*) as bookings, SUM(bookings.total_amount) as revenue')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->whereYear('bookings.event_date', $year)
            ->whereNotIn('bookings.status', ['cancelled'])
            ->groupBy('bookings.venue_id', 'venues.name')
            ->orderByDesc('bookings')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'venue'    => $row->venue_name,
                'bookings' => (int) $row->bookings,
                'revenue'  => (float) $row->revenue,
            ]);

        $totals = [
            'revenue'            => (float) Payment::completed()->whereYear('payment_date', $year)->sum('amount'),
            'bookings'           => Booking::whereYear('event_date', $year)->count(),
            'confirmed_bookings' => Booking::whereYear('event_date', $year)->whereIn('status', ['confirmed', 'completed'])->count(),
            'outstanding'        => (float) Booking::whereNotIn('status', ['cancelled', 'completed'])->sum('balance_amount'),
        ];

        $years = range(now()->year - 3, now()->year + 1);

        $recentBookings = Booking::with(['client', 'venue'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'booking_number' => $b->booking_number,
                'client'         => $b->client?->full_name,
                'venue'          => $b->venue?->name,
                'event_date'     => $b->event_date?->toDateString(),
                'total_amount'   => (float) $b->total_amount,
                'status'         => $b->status,
            ]);

        $upcomingBookings = Booking::with(['client', 'venue'])
            ->upcoming()
            ->whereIn('status', ['confirmed', 'tentative'])
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'booking_number' => $b->booking_number,
                'client'         => $b->client?->full_name,
                'venue'          => $b->venue?->name,
                'event_date'     => $b->event_date?->toDateString(),
                'status'         => $b->status,
            ]);

        return Inertia::render('Reports/Index', [
            'months'           => $months,
            'bookingsByStatus' => $bookingsByStatus,
            'topVenues'        => $topVenues,
            'totals'           => $totals,
            'recentBookings'   => $recentBookings,
            'upcomingBookings' => $upcomingBookings,
            'filters'          => compact('year'),
            'years'            => $years,
        ]);
    }

    public function occupancy(Request $request): Response
    {
        $year = $request->integer('year', now()->year);

        $venues = \App\Models\Venue::withCount([
            'bookings as booked_days' => fn ($q) =>
                $q->whereYear('event_date', $year)->whereNotIn('status', ['cancelled']),
        ])->orderByDesc('booked_days')->get(['id', 'name']);

        $daysInYear = date('L', mktime(0, 0, 0, 1, 1, $year)) ? 366 : 365;

        $venueOccupancy = $venues->map(fn ($v) => [
            'venue'        => $v->name,
            'booked_days'  => $v->booked_days,
            'occupancy_pct' => $daysInYear > 0 ? round(($v->booked_days / $daysInYear) * 100, 1) : 0,
        ]);

        $monthlyOccupancy = collect(range(1, 12))->map(function ($m) use ($year) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $year);
            $bookings    = \App\Models\Booking::whereYear('event_date', $year)
                ->whereMonth('event_date', $m)
                ->whereNotIn('status', ['cancelled'])
                ->count();
            return [
                'month'         => $m,
                'label'         => date('M', mktime(0, 0, 0, $m, 1)),
                'bookings'      => $bookings,
                'occupancy_pct' => $daysInMonth > 0 ? round(($bookings / $daysInMonth) * 100, 1) : 0,
            ];
        });

        return Inertia::render('Reports/Occupancy', [
            'venueOccupancy'   => $venueOccupancy,
            'monthlyOccupancy' => $monthlyOccupancy,
            'filters'          => compact('year'),
            'years'            => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function revenue(Request $request): Response
    {
        $year = $request->integer('year', now()->year);

        $byMonth = Payment::completed()
            ->whereYear('payment_date', $year)
            ->selectRaw('MONTH(payment_date) as month, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = collect(range(1, 12))->map(fn ($m) => [
            'month'   => $m,
            'label'   => date('F', mktime(0, 0, 0, $m, 1)),
            'revenue' => (float) ($byMonth[$m]?->total ?? 0),
            'count'   => (int) ($byMonth[$m]?->count ?? 0),
        ]);

        $byMethod = Payment::completed()
            ->whereYear('payment_date', $year)
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($r) => ['method' => $r->payment_method, 'total' => (float) $r->total, 'count' => $r->count]);

        $totals = [
            'collected'   => (float) Payment::completed()->whereYear('payment_date', $year)->sum('amount'),
            'outstanding' => (float) Booking::whereNotIn('status', ['cancelled', 'completed'])->sum('balance_amount'),
            'refunded'    => (float) Payment::where('status', 'refunded')->whereYear('payment_date', $year)->sum('amount'),
        ];

        return Inertia::render('Reports/Revenue', [
            'months'  => $months,
            'byMethod' => $byMethod,
            'totals'  => $totals,
            'filters' => compact('year'),
            'years'   => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function bookings(Request $request): Response
    {
        $year = $request->integer('year', now()->year);

        $byMonth = Booking::whereYear('event_date', $year)
            ->selectRaw('MONTH(event_date) as month, status, COUNT(*) as count')
            ->groupBy('month', 'status')
            ->get()
            ->groupBy('month');

        $months = collect(range(1, 12))->map(function ($m) use ($byMonth) {
            $rows = $byMonth[$m] ?? collect();
            return [
                'month'     => $m,
                'label'     => date('F', mktime(0, 0, 0, $m, 1)),
                'total'     => $rows->sum('count'),
                'confirmed' => $rows->where('status', 'confirmed')->sum('count'),
                'completed' => $rows->where('status', 'completed')->sum('count'),
                'cancelled' => $rows->where('status', 'cancelled')->sum('count'),
            ];
        });

        $byEventType = Booking::whereYear('event_date', $year)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('event_type, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['type' => $r->event_type, 'count' => $r->count, 'revenue' => (float) $r->revenue]);

        $totals = [
            'total'     => Booking::whereYear('event_date', $year)->count(),
            'confirmed' => Booking::whereYear('event_date', $year)->whereIn('status', ['confirmed', 'completed'])->count(),
            'cancelled' => Booking::whereYear('event_date', $year)->where('status', 'cancelled')->count(),
            'revenue'   => (float) Booking::whereYear('event_date', $year)->whereNotIn('status', ['cancelled'])->sum('total_amount'),
        ];

        return Inertia::render('Reports/Bookings', [
            'months'      => $months,
            'byEventType' => $byEventType,
            'totals'      => $totals,
            'filters'     => compact('year'),
            'years'       => range(now()->year - 3, now()->year + 1),
        ]);
    }
}
