<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Venue;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$start, $end, $meta] = $this->resolveRange($request);

        $months = $this->revenueMonths($start, $end)
            ->map(fn ($m) => ['label' => $m['label'], 'revenue' => $m['revenue']]);

        $bookingsByStatus = Booking::whereBetween('event_date', [$start, $end])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $topVenues = Booking::selectRaw('bookings.venue_id, venues.name as venue_name, COUNT(*) as bookings, SUM(bookings.total_amount) as revenue')
            ->join('venues', 'bookings.venue_id', '=', 'venues.id')
            ->whereBetween('bookings.event_date', [$start, $end])
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
            'revenue'            => (float) Payment::completed()->whereBetween('payment_date', [$start, $end])->sum('amount'),
            'bookings'           => Booking::whereBetween('event_date', [$start, $end])->count(),
            'confirmed_bookings' => Booking::whereBetween('event_date', [$start, $end])->whereIn('status', ['confirmed', 'completed'])->count(),
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
            'filters'          => $meta,
            'years'            => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function revenue(Request $request): Response
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $data = $this->revenueData($start, $end);

        return Inertia::render('Reports/Revenue', [
            'months'   => $data['months'],
            'byMethod' => $data['byMethod'],
            'totals'   => $data['totals'],
            'filters'  => $meta,
            'years'    => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function bookings(Request $request): Response
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $data = $this->bookingsData($start, $end);

        return Inertia::render('Reports/Bookings', [
            'months'      => $data['months'],
            'byEventType' => $data['byEventType'],
            'totals'      => $data['totals'],
            'filters'     => $meta,
            'years'       => range(now()->year - 3, now()->year + 1),
        ]);
    }

    public function occupancy(Request $request): Response
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $data = $this->occupancyData($start, $end);

        return Inertia::render('Reports/Occupancy', [
            'venueOccupancy'   => $data['venueOccupancy'],
            'monthlyOccupancy' => $data['monthlyOccupancy'],
            'filters'          => $meta,
            'years'            => range(now()->year - 3, now()->year + 1),
        ]);
    }

    // ---- CSV exports ----------------------------------------------------

    public function exportRevenue(Request $request): StreamedResponse
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $rows = $this->revenueData($start, $end)['months'];

        return $this->streamCsv("revenue-{$meta['slug']}.csv",
            ['Month', 'Revenue', 'Payments'],
            $rows->map(fn ($m) => [$m['label'], $m['revenue'], $m['count']])->all()
        );
    }

    public function exportBookings(Request $request): StreamedResponse
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $rows = $this->bookingsData($start, $end)['months'];

        return $this->streamCsv("bookings-{$meta['slug']}.csv",
            ['Month', 'Total', 'Confirmed', 'Completed', 'Cancelled'],
            $rows->map(fn ($m) => [$m['label'], $m['total'], $m['confirmed'], $m['completed'], $m['cancelled']])->all()
        );
    }

    public function exportOccupancy(Request $request): StreamedResponse
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $rows = $this->occupancyData($start, $end)['venueOccupancy'];

        return $this->streamCsv("occupancy-{$meta['slug']}.csv",
            ['Venue', 'Booked Days', 'Occupancy %'],
            $rows->map(fn ($v) => [$v['venue'], $v['booked_days'], $v['occupancy_pct']])->all()
        );
    }

    // ---- PDF exports ----------------------------------------------------

    public function pdfRevenue(Request $request): SymfonyResponse
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $data = $this->revenueData($start, $end);

        return Pdf::loadView('pdf.reports.revenue', [
            'period'  => $meta['label'],
            'months'  => $data['months'],
            'byMethod' => $data['byMethod'],
            'totals'  => $data['totals'],
        ])->download("revenue-{$meta['slug']}.pdf");
    }

    public function pdfBookings(Request $request): SymfonyResponse
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $data = $this->bookingsData($start, $end);

        return Pdf::loadView('pdf.reports.bookings', [
            'period'      => $meta['label'],
            'months'      => $data['months'],
            'byEventType' => $data['byEventType'],
            'totals'      => $data['totals'],
        ])->download("bookings-{$meta['slug']}.pdf");
    }

    public function pdfOccupancy(Request $request): SymfonyResponse
    {
        [$start, $end, $meta] = $this->resolveRange($request);
        $data = $this->occupancyData($start, $end);

        return Pdf::loadView('pdf.reports.occupancy', [
            'period'         => $meta['label'],
            'venueOccupancy' => $data['venueOccupancy'],
        ])->download("occupancy-{$meta['slug']}.pdf");
    }

    // ---- Range resolution ----------------------------------------------

    /**
     * Resolve the reporting window from the request. A custom from/to pair wins;
     * otherwise the whole requested (or current) year is used.
     *
     * @return array{0: Carbon, 1: Carbon, 2: array<string, mixed>}
     */
    private function resolveRange(Request $request): array
    {
        $year = $request->integer('year', now()->year);
        $from = $request->date('from');
        $to   = $request->date('to');

        if ($from && $to) {
            if ($from->greaterThan($to)) {
                [$from, $to] = [$to, $from];
            }
            $start = $from->copy()->startOfDay();
            $end   = $to->copy()->endOfDay();
            $label = $start->format('M j, Y') . ' – ' . $end->format('M j, Y');
            $slug  = $start->format('Y-m-d') . '_' . $end->format('Y-m-d');
            $meta  = ['from' => $start->toDateString(), 'to' => $end->toDateString(), 'year' => $year, 'label' => $label, 'slug' => $slug];
        } else {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end   = Carbon::create($year, 12, 31)->endOfDay();
            $meta  = ['from' => null, 'to' => null, 'year' => $year, 'label' => (string) $year, 'slug' => (string) $year];
        }

        return [$start, $end, $meta];
    }

    /**
     * Whole-month buckets spanning the range (capped at 36 to bound chart size).
     *
     * @return Collection<int, array{key: string, label: string, year: int, month: int}>
     */
    private function monthBuckets(Carbon $start, Carbon $end): Collection
    {
        $buckets = collect();
        $cursor  = $start->copy()->startOfMonth();
        $last    = $end->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($last) && $buckets->count() < 36) {
            $buckets->push([
                'key'   => $cursor->format('Y-m'),
                'label' => $cursor->format('M y'),
                'year'  => (int) $cursor->format('Y'),
                'month' => (int) $cursor->format('n'),
            ]);
            $cursor->addMonth();
        }

        return $buckets;
    }

    // ---- Shared data builders ------------------------------------------

    private function revenueMonths(Carbon $start, Carbon $end): Collection
    {
        $byMonth = Payment::completed()
            ->whereBetween('payment_date', [$start, $end])
            ->get(['amount', 'payment_date'])
            ->groupBy(fn ($p) => $p->payment_date->format('Y-m'));

        return $this->monthBuckets($start, $end)->map(fn ($b) => [
            'label'   => $b['label'],
            'revenue' => (float) ($byMonth->get($b['key'])?->sum('amount') ?? 0),
            'count'   => (int) ($byMonth->get($b['key'])?->count() ?? 0),
        ]);
    }

    private function revenueData(Carbon $start, Carbon $end): array
    {
        $months = $this->revenueMonths($start, $end);

        $byMethod = Payment::completed()
            ->whereBetween('payment_date', [$start, $end])
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($r) => ['method' => $r->payment_method, 'total' => (float) $r->total, 'count' => $r->count]);

        $totals = [
            'collected'   => (float) Payment::completed()->whereBetween('payment_date', [$start, $end])->sum('amount'),
            'outstanding' => (float) Booking::whereNotIn('status', ['cancelled', 'completed'])->sum('balance_amount'),
            'refunded'    => (float) Payment::where('status', 'refunded')->whereBetween('payment_date', [$start, $end])->sum('amount'),
        ];

        return compact('months', 'byMethod', 'totals');
    }

    private function bookingsData(Carbon $start, Carbon $end): array
    {
        $byMonth = Booking::whereBetween('event_date', [$start, $end])
            ->get(['status', 'event_date'])
            ->groupBy(fn ($b) => $b->event_date->format('Y-m'));

        $months = $this->monthBuckets($start, $end)->map(function ($b) use ($byMonth) {
            $rows = $byMonth->get($b['key']) ?? collect();
            return [
                'label'     => $b['label'],
                'total'     => $rows->count(),
                'confirmed' => $rows->where('status', 'confirmed')->count(),
                'completed' => $rows->where('status', 'completed')->count(),
                'cancelled' => $rows->where('status', 'cancelled')->count(),
            ];
        });

        $byEventType = Booking::whereBetween('event_date', [$start, $end])
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('event_type, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['type' => $r->event_type, 'count' => $r->count, 'revenue' => (float) $r->revenue]);

        $totals = [
            'total'     => Booking::whereBetween('event_date', [$start, $end])->count(),
            'confirmed' => Booking::whereBetween('event_date', [$start, $end])->whereIn('status', ['confirmed', 'completed'])->count(),
            'cancelled' => Booking::whereBetween('event_date', [$start, $end])->where('status', 'cancelled')->count(),
            'revenue'   => (float) Booking::whereBetween('event_date', [$start, $end])->whereNotIn('status', ['cancelled'])->sum('total_amount'),
        ];

        return compact('months', 'byEventType', 'totals');
    }

    private function occupancyData(Carbon $start, Carbon $end): array
    {
        $venues = Venue::withCount([
            'bookings as booked_days' => fn ($q) =>
                $q->whereBetween('event_date', [$start, $end])->whereNotIn('status', ['cancelled']),
        ])->orderByDesc('booked_days')->get(['id', 'name']);

        $totalDays = max(1, $start->diffInDays($end) + 1);

        $venueOccupancy = $venues->map(fn ($v) => [
            'venue'         => $v->name,
            'booked_days'   => $v->booked_days,
            'occupancy_pct' => round(($v->booked_days / $totalDays) * 100, 1),
        ]);

        $monthlyOccupancy = $this->monthBuckets($start, $end)->map(function ($b) use ($start, $end) {
            $monthStart = Carbon::create($b['year'], $b['month'], 1)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();
            $rangeStart = $monthStart->lessThan($start) ? $start : $monthStart;
            $rangeEnd   = $monthEnd->greaterThan($end) ? $end : $monthEnd;
            $days       = max(1, $rangeStart->diffInDays($rangeEnd) + 1);

            $bookings = Booking::whereBetween('event_date', [$rangeStart, $rangeEnd])
                ->whereNotIn('status', ['cancelled'])
                ->count();

            return [
                'label'         => $b['label'],
                'bookings'      => $bookings,
                'occupancy_pct' => round(($bookings / $days) * 100, 1),
            ];
        });

        return compact('venueOccupancy', 'monthlyOccupancy');
    }

    private function streamCsv(string $filename, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_map([$this, 'csvSafe'], $header));
            foreach ($rows as $row) {
                fputcsv($out, array_map([$this, 'csvSafe'], $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Prevent CSV formula injection: prefix cells starting with a formula
     * trigger character so spreadsheet apps treat them as text.
     */
    private function csvSafe($value): string
    {
        $string = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $string) ? "'" . $string : $string;
    }
}
