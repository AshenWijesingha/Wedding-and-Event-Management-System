<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function revenue(Request $request): JsonResponse
    {
        $from = $request->from ?? now()->startOfMonth();
        $to = $request->to ?? now()->endOfMonth();

        $revenue = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        return response()->json([
            'total_revenue' => $revenue,
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $from = $request->from ?? now()->startOfMonth();
        $to = $request->to ?? now()->endOfMonth();

        $bookings = Booking::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'bookings_by_status' => $bookings,
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function inquiries(Request $request): JsonResponse
    {
        $from = $request->from ?? now()->startOfMonth();
        $to = $request->to ?? now()->endOfMonth();

        $inquiries = Inquiry::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'inquiries_by_status' => $inquiries,
            'period' => ['from' => $from, 'to' => $to],
        ]);
    }
}
