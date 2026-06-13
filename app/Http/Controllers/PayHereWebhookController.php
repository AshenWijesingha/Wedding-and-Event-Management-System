<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Services\PaymentService;
use App\Services\PayHereService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public, CSRF-exempt, tenant-agnostic handler for PayHere's server-to-server
 * `notify_url`. This is the single source of truth for online payment state.
 *
 * Every check must pass, in order. Any failure returns without mutating state.
 */
class PayHereWebhookController extends Controller
{
    public function __construct(
        private PayHereService $payhere,
        private PaymentService $payments,
    ) {
    }

    public function notify(Request $request): Response
    {
        $payload = $request->all();

        // 1. Look up the Payment by order_id across all tenants. Unknown → ignore.
        $payment = Payment::withoutGlobalScopes()->find($payload['order_id'] ?? null);
        if (! $payment) {
            return response('', 200);
        }

        // 2. Derive the tenant from the Payment.
        $tenant = Tenant::find($payment->tenant_id);
        if (! $tenant) {
            return response('', 200);
        }

        // 3. Recompute md5sig and constant-time compare.
        if (! $this->payhere->verifyNotification($payload, $tenant)) {
            logger()->warning('PayHere webhook signature mismatch', ['payment_id' => $payment->id]);

            return response('Invalid signature', 400);
        }

        // 4. Cross-check merchant_id, amount, currency against the Payment.
        $expectedMerchant = $this->payhere->credentialsFor($tenant)['merchant_id'] ?? null;
        $amountMatches    = number_format((float) $payment->amount, 2, '.', '') === (string) ($payload['payhere_amount'] ?? '');
        $currencyMatches  = (string) $payment->currency === (string) ($payload['payhere_currency'] ?? '');

        if ((string) ($payload['merchant_id'] ?? '') !== (string) $expectedMerchant || ! $amountMatches || ! $currencyMatches) {
            logger()->warning('PayHere webhook field mismatch', ['payment_id' => $payment->id]);

            return response('Field mismatch', 400);
        }

        // 5. Idempotency: already completed → no-op.
        if ($payment->status === 'completed') {
            return response('', 200);
        }

        // 6. Apply the verified result.
        $this->payments->applyGatewayResult($payment, $payload);

        return response('', 200);
    }
}
