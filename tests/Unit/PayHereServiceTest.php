<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\PayHereService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PayHereServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): PayHereService
    {
        return app(PayHereService::class);
    }

    private function tenantWithCreds(string $secret = 'sample_secret'): Tenant
    {
        $tenant = Tenant::factory()->create();
        $tenant->setSetting('payhere', [
            'merchant_id'     => '1211149',
            'merchant_secret' => $secret,
            'sandbox'         => true,
            'currency'        => 'LKR',
        ]);

        return $tenant->fresh();
    }

    public function test_credentials_resolve_from_tenant_settings(): void
    {
        $tenant = $this->tenantWithCreds();

        $creds = $this->service()->credentialsFor($tenant);

        $this->assertSame('1211149', $creds['merchant_id']);
        $this->assertSame('sample_secret', $creds['merchant_secret']);
        $this->assertTrue($creds['sandbox']);
    }

    public function test_credentials_fall_back_to_config(): void
    {
        config([
            'services.payhere.merchant_id'     => 'CFG123',
            'services.payhere.merchant_secret' => 'cfg_secret',
            'services.payhere.sandbox'         => true,
            'services.payhere.currency'        => 'LKR',
        ]);

        $creds = $this->service()->credentialsFor(Tenant::factory()->create());

        $this->assertSame('CFG123', $creds['merchant_id']);
        $this->assertSame('cfg_secret', $creds['merchant_secret']);
    }

    public function test_encrypted_secret_round_trips_and_ciphertext_differs(): void
    {
        $svc    = $this->service();
        $cipher = $svc->encryptSecret('top_secret');

        $this->assertNotSame('top_secret', $cipher);
        $this->assertSame('top_secret', Crypt::decryptString($cipher));

        $tenant = Tenant::factory()->create();
        $tenant->setSetting('payhere', [
            'merchant_id'     => 'M1',
            'merchant_secret' => $cipher,
        ]);

        $this->assertSame('top_secret', $svc->credentialsFor($tenant->fresh())['merchant_secret']);
    }

    public function test_verify_notification_accepts_valid_and_rejects_tampered(): void
    {
        $tenant = $this->tenantWithCreds('sample_secret');
        $svc    = $this->service();

        $merchantId = '1211149';
        $orderId    = '42';
        $amount     = '1000.00';
        $currency   = 'LKR';
        $statusCode = '2';

        $md5sig = strtoupper(md5(
            $merchantId . $orderId . $amount . $currency . $statusCode . strtoupper(md5('sample_secret'))
        ));

        $valid = [
            'merchant_id'      => $merchantId,
            'order_id'         => $orderId,
            'payhere_amount'   => $amount,
            'payhere_currency' => $currency,
            'status_code'      => $statusCode,
            'md5sig'           => $md5sig,
        ];

        $this->assertTrue($svc->verifyNotification($valid, $tenant));

        $tampered = array_merge($valid, ['payhere_amount' => '9999.00']);
        $this->assertFalse($svc->verifyNotification($tampered, $tenant));
    }

    public function test_map_status_code(): void
    {
        $svc = $this->service();

        $this->assertSame('completed', $svc->mapStatusCode(2));
        $this->assertSame('pending', $svc->mapStatusCode(0));
        $this->assertSame('failed', $svc->mapStatusCode(-1));
        $this->assertSame('failed', $svc->mapStatusCode(-2));
        $this->assertSame('refunded', $svc->mapStatusCode(-3));
    }
}
