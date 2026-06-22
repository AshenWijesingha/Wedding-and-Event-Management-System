<?php

namespace Tests\Feature\Payments;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function tenantWithPayHere(string $secret = 'sample_secret', string $merchantId = '1211149'): Tenant
    {
        $tenant = Tenant::factory()->create();
        $tenant->setSetting('payhere', [
            'merchant_id' => $merchantId,
            'merchant_secret' => $secret,
            'sandbox' => true,
            'currency' => 'LKR',
        ]);

        return $tenant->fresh();
    }

    private function bookingFor(Tenant $tenant, array $attrs = []): Booking
    {
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);
        $venue = Venue::factory()->create(['tenant_id' => $tenant->id]);

        return Booking::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'venue_id' => $venue->id,
            'total_amount' => 10000,
            'paid_amount' => 0,
            'balance_amount' => 10000,
        ], $attrs));
    }

    private function signedPayload(string $secret, array $over = []): array
    {
        $base = array_merge([
            'merchant_id' => '1211149',
            'order_id' => '1',
            'payhere_amount' => '5000.00',
            'payhere_currency' => 'LKR',
            'status_code' => '2',
            'payment_id' => 'PH-TEST-001',
        ], $over);

        $base['md5sig'] = strtoupper(md5(
            $base['merchant_id']
            . $base['order_id']
            . $base['payhere_amount']
            . $base['payhere_currency']
            . $base['status_code']
            . strtoupper(md5($secret))
        ));

        return $base;
    }

    // ----- Webhook ------------------------------------------------------------

    public function test_webhook_success_completes_payment_and_updates_booking(): void
    {
        $tenant = $this->tenantWithPayHere();
        $booking = $this->bookingFor($tenant);
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'gateway' => 'payhere',
            'amount' => 5000,
            'currency' => 'LKR',
        ]);

        $payload = $this->signedPayload('sample_secret', ['order_id' => (string) $payment->id]);

        $this->post('/payhere/notify', $payload)->assertOk();

        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertSame('PH-TEST-001', $payment->fresh()->gateway_payment_id);
        $this->assertEquals(5000, $booking->fresh()->paid_amount);
        $this->assertEquals(5000, $booking->fresh()->balance_amount);
    }

    public function test_webhook_bad_signature_rejected_no_state_change(): void
    {
        $tenant = $this->tenantWithPayHere();
        $booking = $this->bookingFor($tenant);
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'amount' => 5000,
            'currency' => 'LKR',
        ]);

        $payload = $this->signedPayload('sample_secret', ['order_id' => (string) $payment->id]);
        $payload['md5sig'] = 'DEADBEEF';

        $this->post('/payhere/notify', $payload)->assertStatus(400);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertEquals(0, $booking->fresh()->paid_amount);
    }

    public function test_webhook_amount_mismatch_rejected(): void
    {
        $tenant = $this->tenantWithPayHere();
        $booking = $this->bookingFor($tenant);
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'amount' => 5000,
            'currency' => 'LKR',
        ]);

        // Sign a payload whose amount differs from the stored payment amount.
        $payload = $this->signedPayload('sample_secret', [
            'order_id' => (string) $payment->id,
            'payhere_amount' => '9999.00',
        ]);

        $this->post('/payhere/notify', $payload)->assertStatus(400);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_webhook_cross_tenant_secret_rejected(): void
    {
        $tenantA = $this->tenantWithPayHere('secret_a', 'MERCH_A');
        $tenantB = $this->tenantWithPayHere('secret_b', 'MERCH_B');

        $booking = $this->bookingFor($tenantA);
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $tenantA->id,
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'amount' => 5000,
            'currency' => 'LKR',
        ]);

        // Signed with tenant B's secret/merchant for a tenant A payment.
        $payload = $this->signedPayload('secret_b', [
            'order_id' => (string) $payment->id,
            'merchant_id' => 'MERCH_B',
        ]);

        $this->post('/payhere/notify', $payload)->assertStatus(400);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_webhook_replay_is_idempotent(): void
    {
        $tenant = $this->tenantWithPayHere();
        $booking = $this->bookingFor($tenant);
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'amount' => 5000,
            'currency' => 'LKR',
        ]);

        $payload = $this->signedPayload('sample_secret', ['order_id' => (string) $payment->id]);

        $this->post('/payhere/notify', $payload)->assertOk();
        $this->post('/payhere/notify', $payload)->assertOk();

        $this->assertEquals(5000, $booking->fresh()->paid_amount);
        $this->assertSame(1, Payment::withoutGlobalScopes()->where('booking_id', $booking->id)->where('status', 'completed')->count());
    }

    public function test_webhook_canceled_marks_failed_booking_untouched(): void
    {
        $tenant = $this->tenantWithPayHere();
        $booking = $this->bookingFor($tenant);
        $payment = Payment::factory()->pending()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'amount' => 5000,
            'currency' => 'LKR',
        ]);

        $payload = $this->signedPayload('sample_secret', [
            'order_id' => (string) $payment->id,
            'status_code' => '-1',
        ]);

        $this->post('/payhere/notify', $payload)->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertEquals(0, $booking->fresh()->paid_amount);
    }

    public function test_unknown_order_id_is_ignored(): void
    {
        $this->post('/payhere/notify', ['order_id' => 999999])->assertOk();
    }

    // ----- Manual record (admin) ---------------------------------------------

    public function test_admin_records_manual_payment_and_updates_booking(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = $this->bookingFor(Tenant::find($admin->tenant_id));

        $this->actingAs($admin)
            ->post("/admin/bookings/{$booking->id}/payments", [
                'amount' => 4000,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertEquals(4000, $booking->fresh()->paid_amount);
        $this->assertEquals(6000, $booking->fresh()->balance_amount);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'status' => 'completed',
            'received_by' => $admin->id,
        ]);
    }

    public function test_manual_overpay_blocked_without_override(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = $this->bookingFor(Tenant::find($admin->tenant_id));

        $this->actingAs($admin)
            ->post("/admin/bookings/{$booking->id}/payments", [
                'amount' => 99999,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(0, $booking->fresh()->paid_amount);
    }

    public function test_admin_receipt_renders_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = $this->bookingFor(Tenant::find($admin->tenant_id));
        $payment = Payment::factory()->completed()->create([
            'tenant_id' => $admin->tenant_id,
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
        ]);

        $response = $this->actingAs($admin)->get("/admin/payments/{$payment->id}/receipt");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    // ----- Portal initiate / RBAC --------------------------------------------

    public function test_client_initiate_creates_pending_payment_for_own_booking(): void
    {
        $tenant = $this->tenantWithPayHere();
        $user = User::factory()->client()->create(['tenant_id' => $tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
        $booking = $this->bookingFor($tenant, ['client_id' => $client->id]);

        $this->actingAs($user)
            ->post('/portal/payments/initiate', ['booking_id' => $booking->id, 'amount' => 3000])
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'gateway' => 'payhere',
            'status' => 'pending',
            'amount' => 3000,
        ]);
    }

    public function test_client_cannot_pay_someone_elses_booking(): void
    {
        $tenant = $this->tenantWithPayHere();
        $user = User::factory()->client()->create(['tenant_id' => $tenant->id]);
        Client::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
        $otherBooking = $this->bookingFor($tenant); // belongs to a different client

        $this->actingAs($user)
            ->post('/portal/payments/initiate', ['booking_id' => $otherBooking->id, 'amount' => 1000])
            ->assertNotFound();
    }

    public function test_client_cannot_access_admin_payment_routes(): void
    {
        $tenant = $this->tenantWithPayHere();
        $user = User::factory()->client()->create(['tenant_id' => $tenant->id]);
        $booking = $this->bookingFor($tenant);

        $this->actingAs($user)
            ->get("/admin/bookings/{$booking->id}/payments/create")
            ->assertForbidden();
    }
}
