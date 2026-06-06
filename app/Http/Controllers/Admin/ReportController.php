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

        $topVenues = Booking::with('venue')
            ->whereYear('event_date', $year)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('venue_id, COUNT(*) as bookings, SUM(total_amount) as revenue')
            ->groupBy('venue_id')
            ->orderByDesc('bookings')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'venue'    => $row->venue?->name,
                'bookings' => $row->bookings,
                'revenue'  => (float) $row->revenue,
            ]);

        $totals = [
            'revenue'            => (float) Payment::completed()->whereYear('payment_date', $year)->sum('amount'),
            'bookings'           => Booking::whereYear('event_date', $year)->count(),
            'confirmed_bookings' => Booking::whereYear('event_date', $year)->whereIn('status', ['confirmed', 'completed'])->count(),
            'outstanding'        => (float) Booking::whereNotIn('status', ['cancelled', 'completed'])->sum('balance_amount'),
        ];

        $years = range(now()->year - 3, now()->year + 1);

        return Inertia::render('Reports/Index', [
            'months'           => $months,
            'bookingsByStatus' => $bookingsByStatus,
            'topVenues'        => $topVenues,
            'totals'           => $totals,
            'filters'          => compact('year'),
            'years'            => $years,
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
