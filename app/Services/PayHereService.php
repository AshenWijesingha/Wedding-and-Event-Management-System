<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Facades\Crypt;

/**
 * PayHere hosted-checkout integration.
 *
 * Responsible only for credential resolution, building the signed checkout
 * payload, and verifying inbound webhook signatures. State mutation lives in
 * {@see PaymentService}; the webhook is the single source of truth.
 */
class PayHereService
{
    /**
     * Resolve PayHere credentials for a tenant: tenant settings first, then the
     * platform demo account from config. Returns null when nothing is configured.
     *
     * @return array{merchant_id:string,merchant_secret:string,sandbox:bool,currency:string}|null
     */
    public function credentialsFor(Tenant $tenant): ?array
    {
        $settings = $tenant->getSetting('payhere');

        if (is_array($settings) && ! empty($settings['merchant_id']) && ! empty($settings['merchant_secret'])) {
            return [
                'merchant_id'     => (string) $settings['merchant_id'],
                'merchant_secret' => $this->decryptSecret($settings['merchant_secret']),
                'sandbox'         => (bool) ($settings['sandbox'] ?? true),
                'currency'        => $settings['currency'] ?? 'LKR',
            ];
        }

        $config = config('services.payhere');

        if (! empty($config['merchant_id']) && ! empty($config['merchant_secret'])) {
            return [
                'merchant_id'     => (string) $config['merchant_id'],
                'merchant_secret' => (string) $config['merchant_secret'],
                'sandbox'         => (bool) ($config['sandbox'] ?? true),
                'currency'        => $config['currency'] ?? 'LKR',
            ];
        }

        return null;
    }

    /**
     * Whether the tenant can take PayHere payments.
     */
    public function isConfigured(Tenant $tenant): bool
    {
        return $this->credentialsFor($tenant) !== null;
    }

    /**
     * Hosted-checkout endpoint for the given mode.
     */
    public function checkoutUrl(bool $sandbox): string
    {
        return $sandbox
            ? 'https://sandbox.payhere.lk/pay/checkout'
            : 'https://www.payhere.lk/pay/checkout';
    }

    /**
     * Build the auto-submitting checkout form payload (action + signed fields).
     *
     * @return array{action:string,fields:array<string,string>}
     */
    public function buildCheckout(Payment $payment): array
    {
        $tenant = $payment->booking?->tenant ?? Tenant::find($payment->tenant_id);
        $creds  = $tenant ? $this->credentialsFor($tenant) : null;

        if ($creds === null) {
            throw new \RuntimeException('PayHere is not configured for this tenant.');
        }

        $orderId   = (string) $payment->id;
        $amount2dp = number_format((float) $payment->amount, 2, '.', '');
        $currency  = $payment->currency ?: $creds['currency'];

        $hash = strtoupper(md5(
            $creds['merchant_id']
            . $orderId
            . $amount2dp
            . $currency
            . strtoupper(md5($creds['merchant_secret']))
        ));

        $client  = $payment->client;
        $booking = $payment->booking;

        $fields = [
            'merchant_id' => $creds['merchant_id'],
            'return_url'  => route('portal.payments.return'),
            'cancel_url'  => route('portal.payments.cancel', ['payment' => $payment->id]),
            'notify_url'  => route('payhere.notify'),
            'order_id'    => $orderId,
            'items'       => $payment->installment_name ?: ('Booking ' . ($booking?->booking_number ?? $orderId)),
            'currency'    => $currency,
            'amount'      => $amount2dp,
            'first_name'  => (string) ($client?->first_name ?? ''),
            'last_name'   => (string) ($client?->last_name ?? ''),
            'email'       => (string) ($client?->email ?? ''),
            'phone'       => (string) ($client?->phone ?? ''),
            'address'     => (string) ($client?->address ?? ''),
            'city'        => (string) ($client?->city ?? ''),
            'country'     => 'Sri Lanka',
            'hash'        => $hash,
        ];

        return [
            'action' => $this->checkoutUrl($creds['sandbox']),
            'fields' => $fields,
        ];
    }

    /**
     * Verify an inbound webhook signature against the tenant's secret.
     * Constant-time comparison; never mutates state.
     */
    public function verifyNotification(array $payload, Tenant $tenant): bool
    {
        $creds = $this->credentialsFor($tenant);

        if ($creds === null) {
            return false;
        }

        $local = strtoupper(md5(
            ($payload['merchant_id'] ?? '')
            . ($payload['order_id'] ?? '')
            . ($payload['payhere_amount'] ?? '')
            . ($payload['payhere_currency'] ?? '')
            . ($payload['status_code'] ?? '')
            . strtoupper(md5($creds['merchant_secret']))
        ));

        return hash_equals($local, (string) ($payload['md5sig'] ?? ''));
    }

    /**
     * Map a PayHere status_code to our internal payment status.
     */
    public function mapStatusCode(int $code): string
    {
        return match ($code) {
            2       => 'completed',
            0       => 'pending',
            -3      => 'refunded',
            default => 'failed', // -1 canceled, -2 failed
        };
    }

    /**
     * Encrypt a merchant secret for storage in tenant settings.
     */
    public function encryptSecret(string $plain): string
    {
        return Crypt::encryptString($plain);
    }

    /**
     * Decrypt a stored merchant secret, tolerating already-plaintext values.
     */
    private function decryptSecret(string $stored): string
    {
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            // Value was never encrypted (e.g. seeded fixture) — use as-is.
            return $stored;
        }
    }
}
