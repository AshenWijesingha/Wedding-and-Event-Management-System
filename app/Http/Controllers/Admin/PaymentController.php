<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::with(['booking', 'client'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->method, fn ($q, $method) => $q->where('payment_method', $method))
            ->orderBy('payment_date', 'desc')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total'      => Payment::completed()->sum('amount'),
            'this_month' => Payment::completed()->whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'pending'    => Payment::where('status', 'pending')->sum('amount'),
        ];

        return Inertia::render('Payments/Index', [
            'payments' => PaymentResource::collection($payments),
            'filters'  => $request->only(['status', 'method']),
            'summary'  => $summary,
        ]);
    }
}
