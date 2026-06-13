<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\BrandingService;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private BrandingService $branding,
    ) {
    }

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

    /**
     * Show the manual-payment form for a booking.
     */
    public function create(Booking $booking): Response
    {
        $booking->load('client');

        return Inertia::render('Payments/Create', [
            'booking' => [
                'id'             => $booking->id,
                'booking_number' => $booking->booking_number,
                'client_name'    => $booking->client?->full_name,
                'total_amount'   => (float) $booking->total_amount,
                'paid_amount'    => (float) $booking->paid_amount,
                'balance_amount' => (float) $booking->balance_amount,
            ],
        ]);
    }

    /**
     * Record a manual/offline payment against a booking.
     */
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => 'required|string|in:cash,bank_transfer,cheque,card',
            'payment_date'     => 'nullable|date',
            'installment_name' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
            'allow_overpay'    => 'sometimes|boolean',
        ]);

        $balance = (float) $booking->balance_amount;
        if (empty($data['allow_overpay']) && (float) $data['amount'] > $balance) {
            return back()->withErrors([
                'amount' => "Amount exceeds the outstanding balance of {$balance}. Enable overpay to proceed.",
            ])->withInput();
        }

        $this->payments->recordManual($booking, $data, $request->user());

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Payment recorded.');
    }

    public function show(Payment $payment): Response
    {
        $payment->load(['booking', 'client', 'receivedBy']);

        return Inertia::render('Payments/Show', [
            'payment' => (new PaymentResource($payment))->resolve(),
        ]);
    }

    /**
     * Stream a PDF receipt for a payment.
     */
    public function receipt(Payment $payment): SymfonyResponse
    {
        try {
            $payment->load(['booking', 'client']);

            $pdf = Pdf::loadView('pdf.receipt', [
                'payment'  => $payment,
                'branding' => $this->branding->getBranding(),
            ])->setPaper('a4', 'portrait');

            $safeNumber = preg_replace('/[^a-zA-Z0-9_\-]/', '', $payment->payment_number);

            return $pdf->download("receipt_{$safeNumber}.pdf");
        } catch (\Throwable $e) {
            logger()->error('Receipt PDF generation failed', ['payment_id' => $payment->id, 'exception' => $e]);

            return back()->with('error', 'PDF generation failed. Please try again.');
        }
    }
}
