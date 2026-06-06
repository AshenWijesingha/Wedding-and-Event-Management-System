<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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
            'months'          => $months,
            'bookingsByStatus' => $bookingsByStatus,
            'topVenues'       => $topVenues,
            'totals'          => $totals,
            'filters'         => compact('year'),
            'years'           => $years,
        ]);
    }
}
