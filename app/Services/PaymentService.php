<?php

namespace App\Services;

use App\Mail\PaymentReceivedMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\StaffNotification;
use App\Support\Notifier;
use Illuminate\Support\Facades\DB;

/**
 * Owns all payment-record creation and is the single writer of booking
 * paid/balance totals. Both manual records and gateway results funnel through
 * {@see recalculateBooking}.
 */
class PaymentService
{
    public function __construct(private PayHereService $payhere) {}

    /**
     * Record an offline/manual payment (cash, bank transfer, cheque, card) as
     * completed and recalculate the booking balance.
     */
    public function recordManual(Booking $booking, array $data, User $receivedBy): Payment
    {
        $payment = DB::transaction(function () use ($booking, $data, $receivedBy) {
            $payment = Payment::create([
                'tenant_id' => $booking->tenant_id,
                'payment_number' => $this->generatePaymentNumber($booking->tenant_id),
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
                'installment_name' => $data['installment_name'] ?? null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'LKR',
                'payment_method' => $data['payment_method'],
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'received_by' => $receivedBy->id,
            ]);

            $this->recalculateBooking($booking);

            return $payment;
        });

        $this->notifyPaymentReceived($payment);

        return $payment;
    }

    /**
     * Best-effort client email + staff notification for a completed payment.
     * Tenant is derived from the payment (current tenant is unset in the webhook).
     */
    private function notifyPaymentReceived(Payment $payment): void
    {
        if ($payment->status !== 'completed') {
            return;
        }

        Notifier::mail(
            optional($payment->client)->email,
            new PaymentReceivedMail($payment),
        );
        Notifier::staff(
            $payment->tenant,
            new StaffNotification(
                'payment_received',
                "Payment {$payment->payment_number} was received.",
                "/admin/payments/{$payment->id}",
            ),
        );
    }

    /**
     * Apply a verified PayHere webhook result to a payment and, when relevant,
     * recalculate the owning booking. Callers must have already verified the
     * signature and ownership.
     */
    public function applyGatewayResult(Payment $payment, array $payload): void
    {
        DB::transaction(function () use ($payment, $payload) {
            $code = (int) ($payload['status_code'] ?? -2);
            $status = $this->payhere->mapStatusCode($code);

            $payment->status = $status;
            $payment->gateway_status_code = $code;
            $payment->gateway_payment_id = $payload['payment_id'] ?? $payment->gateway_payment_id;
            $payment->gateway_response = $payload;

            if ($status === 'completed' && empty($payment->payment_date)) {
                $payment->payment_date = now()->toDateString();
            }

            $payment->save();

            if (in_array($status, ['completed', 'refunded'], true)) {
                $booking = Booking::withoutGlobalScopes()->find($payment->booking_id);
                if ($booking) {
                    $this->recalculateBooking($booking);
                }
            }
        });

        $this->notifyPaymentReceived($payment);
    }

    /**
     * Recompute paid_amount/balance_amount from completed payments. The only
     * place booking balances are written. Tenant-scope-agnostic so it works
     * inside the public webhook.
     */
    public function recalculateBooking(Booking $booking): void
    {
        $paid = (float) Payment::withoutGlobalScopes()
            ->where('booking_id', $booking->id)
            ->where('status', 'completed')
            ->sum('amount');

        $booking->paid_amount = $paid;
        $booking->balance_amount = max(0, (float) $booking->total_amount - $paid);
        $booking->save();
    }

    /**
     * Generate a payment number unique within the tenant for the current year.
     */
    public function generatePaymentNumber(int $tenantId): string
    {
        $year = date('Y');
        $next = Payment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        do {
            $number = sprintf('PAY%s%04d', $year, $next);
            $exists = Payment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('payment_number', $number)
                ->exists();
            $next++;
        } while ($exists);

        return $number;
    }
}
