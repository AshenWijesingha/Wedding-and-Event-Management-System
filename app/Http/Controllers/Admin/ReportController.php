<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Venue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->integer('year', now()->year);

        // Portable monthly revenue (SQLite has no MONTH()): group Carbon dates in PHP.
        $revenueByMonth = Payment::completed()
            ->whereYear('payment_date', $year)
            ->get(['amount', 'payment_date'])
            ->groupBy(fn ($p) => (int) $p->payment_date->format('n'))
            ->map(fn ($group) => (float) $group->sum('amount'));

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
            'years'            => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function revenue(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $data = $this->revenueData($year);

        return Inertia::render('Reports/Revenue', [
            'months'  => $data['months'],
            'byMethod' => $data['byMethod'],
            'totals'  => $data['totals'],
            'filters' => compact('year'),
            'years'   => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function bookings(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $data = $this->bookingsData($year);

        return Inertia::render('Reports/Bookings', [
            'months'      => $data['months'],
            'byEventType' => $data['byEventType'],
            'totals'      => $data['totals'],
            'filters'     => compact('year'),
            'years'       => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function occupancy(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $data = $this->occupancyData($year);

        return Inertia::render('Reports/Occupancy', [
            'venueOccupancy'   => $data['venueOccupancy'],
            'monthlyOccupancy' => $data['monthlyOccupancy'],
            'filters'          => compact('year'),
            'years'            => range(now()->year - 3, now()->year + 1),
        ]);
    }

    // ---- CSV exports ----------------------------------------------------

    public function exportRevenue(Request $request): StreamedResponse
    {
        $year = $request->integer('year', now()->year);
        $rows = $this->revenueData($year)['months'];

        return $this->streamCsv("revenue-{$year}.csv",
            ['Month', 'Revenue', 'Payments'],
            $rows->map(fn ($m) => [$m['label'], $m['revenue'], $m['count']])->all()
        );
    }

    public function exportBookings(Request $request): StreamedResponse
    {
        $year = $request->integer('year', now()->year);
        $rows = $this->bookingsData($year)['months'];

        return $this->streamCsv("bookings-{$year}.csv",
            ['Month', 'Total', 'Confirmed', 'Completed', 'Cancelled'],
            $rows->map(fn ($m) => [$m['label'], $m['total'], $m['confirmed'], $m['completed'], $m['cancelled']])->all()
        );
    }

    public function exportOccupancy(Request $request): StreamedResponse
    {
        $year = $request->integer('year', now()->year);
        $rows = $this->occupancyData($year)['venueOccupancy'];

        return $this->streamCsv("occupancy-{$year}.csv",
            ['Venue', 'Booked Days', 'Occupancy %'],
            $rows->map(fn ($v) => [$v['venue'], $v['booked_days'], $v['occupancy_pct']])->all()
        );
    }

    // ---- Shared data builders ------------------------------------------

    private function revenueData(int $year): array
    {
        $byMonth = Payment::completed()
            ->whereYear('payment_date', $year)
            ->get(['amount', 'payment_date'])
            ->groupBy(fn ($p) => (int) $p->payment_date->format('n'));

        $months = collect(range(1, 12))->map(fn ($m) => [
            'month'   => $m,
            'label'   => date('F', mktime(0, 0, 0, $m, 1)),
            'revenue' => (float) ($byMonth->get($m)?->sum('amount') ?? 0),
            'count'   => (int) ($byMonth->get($m)?->count() ?? 0),
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

        return compact('months', 'byMethod', 'totals');
    }

    private function bookingsData(int $year): array
    {
        $byMonth = Booking::whereYear('event_date', $year)
            ->get(['status', 'event_date'])
            ->groupBy(fn ($b) => (int) $b->event_date->format('n'));

        $months = collect(range(1, 12))->map(function ($m) use ($byMonth) {
            $rows = $byMonth[$m] ?? collect();
            return [
                'month'     => $m,
                'label'     => date('F', mktime(0, 0, 0, $m, 1)),
                'total'     => $rows->count(),
                'confirmed' => $rows->where('status', 'confirmed')->count(),
                'completed' => $rows->where('status', 'completed')->count(),
                'cancelled' => $rows->where('status', 'cancelled')->count(),
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

        return compact('months', 'byEventType', 'totals');
    }

    private function occupancyData(int $year): array
    {
        $venues = Venue::withCount([
            'bookings as booked_days' => fn ($q) =>
                $q->whereYear('event_date', $year)->whereNotIn('status', ['cancelled']),
        ])->orderByDesc('booked_days')->get(['id', 'name']);

        $daysInYear = date('L', mktime(0, 0, 0, 1, 1, $year)) ? 366 : 365;

        $venueOccupancy = $venues->map(fn ($v) => [
            'venue'         => $v->name,
            'booked_days'   => $v->booked_days,
            'occupancy_pct' => $daysInYear > 0 ? round(($v->booked_days / $daysInYear) * 100, 1) : 0,
        ]);

        $monthlyOccupancy = collect(range(1, 12))->map(function ($m) use ($year) {
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $year);
            $bookings    = Booking::whereYear('event_date', $year)
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

        return compact('venueOccupancy', 'monthlyOccupancy');
    }

    private function streamCsv(string $filename, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
