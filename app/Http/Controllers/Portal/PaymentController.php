<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\BrandingService;
use App\Services\PayHereService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Client-portal PayHere checkout. Creates the pending Payment and hands the
 * browser to PayHere's hosted checkout. Never marks a payment paid — the
 * webhook ({@see \App\Http\Controllers\PayHereWebhookController}) is authoritative.
 */
class PaymentController extends Controller
{
    public function __construct(private PayHereService $payhere)
    {
    }

    /**
     * Validate ownership + amount, create a pending Payment, and render the
     * auto-submitting checkout form.
     */
    public function initiate(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'booking_id' => 'required|integer',
            'amount'     => 'required|numeric|min:0.01',
        ]);

        $clientId = $this->clientId();

        $booking = Booking::where('id', $data['booking_id'])
            ->where('client_id', $clientId)
            ->firstOrFail();

        if ((float) $data['amount'] > (float) $booking->balance_amount) {
            return back()->withErrors(['amount' => 'Amount exceeds the outstanding balance.']);
        }

        $tenant = Tenant::find($booking->tenant_id);
        if (! $tenant || ! $this->payhere->isConfigured($tenant)) {
            return back()->withErrors(['amount' => 'Online payment is not available right now.']);
        }

        $currency = $this->payhere->credentialsFor($tenant)['currency'] ?? 'LKR';

        $payment = Payment::create([
            'tenant_id'        => $booking->tenant_id,
            'payment_number'   => app(\App\Services\PaymentService::class)->generatePaymentNumber($booking->tenant_id),
            'booking_id'       => $booking->id,
            'client_id'        => $clientId,
            'installment_name' => 'Online Payment',
            'amount'           => $data['amount'],
            'currency'         => $currency,
            'payment_method'   => 'payhere',
            'gateway'          => 'payhere',
            'status'           => 'pending',
        ]);

        $payment->setRelation('booking', $booking);
        $payment->setRelation('client', Client::find($clientId));

        $checkout = $this->payhere->buildCheckout($payment);

        return view('payments.checkout', [
            'action' => $checkout['action'],
            'fields' => $checkout['fields'],
        ]);
    }

    /**
     * Cosmetic landing after PayHere redirect. State is set by the webhook only.
     */
    public function return(Request $request): RedirectResponse
    {
        return redirect()
            ->route('portal.payments')
            ->with('info', 'Thanks! We are confirming your payment — it will appear here shortly.');
    }

    /**
     * Client canceled at PayHere. Mark the still-pending Payment failed.
     */
    public function cancel(Payment $payment): RedirectResponse
    {
        if ($payment->client_id === $this->clientId() && $payment->status === 'pending') {
            $payment->update(['status' => 'failed']);
        }

        return redirect()
            ->route('portal.payments')
            ->with('error', 'Payment canceled. Nothing was charged.');
    }

    /**
     * Download a receipt for the client's own payment.
     */
    public function receipt(Payment $payment, BrandingService $branding): SymfonyResponse
    {
        abort_unless($payment->client_id === $this->clientId(), 403);

        $payment->load(['booking', 'client']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'payment'  => $payment,
            'branding' => $branding->getBranding(),
        ])->setPaper('a4', 'portrait');

        $safeNumber = preg_replace('/[^a-zA-Z0-9_\-]/', '', $payment->payment_number);

        return $pdf->download("receipt_{$safeNumber}.pdf");
    }

    /**
     * Resolve the authenticated client's id, creating a profile if missing.
     */
    private function clientId(): int
    {
        $user = Auth::user();

        $client = $user->client;
        if ($client === null) {
            $parts  = preg_split('/\s+/', trim($user->name ?? ''), 2);
            $client = $user->client()->create([
                'tenant_id'  => $user->tenant_id ?? optional(Tenant::current())->id,
                'first_name' => $parts[0] ?? ($user->name ?? 'Guest'),
                'last_name'  => $parts[1] ?? '',
                'email'      => $user->email,
            ]);
        }

        return $client->id;
    }
}
