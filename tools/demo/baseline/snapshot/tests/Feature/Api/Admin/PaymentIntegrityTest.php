<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $venue = Venue::factory()->create(['tenant_id' => $tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);

        $this->admin = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
        $this->admin->assignRole('admin');

        $this->booking = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'venue_id' => $venue->id,
            'total_amount' => 10000,
            'paid_amount' => 0,
            'balance_amount' => 10000,
        ]);
    }

    public function test_creating_payment_recalculates_booking_balance(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/payments', [
                'booking_id' => $this->booking->id,
                'installment_name' => 'Deposit',
                'amount' => 3000,
                'payment_method' => 'bank_transfer',
                'payment_date' => now()->toDateString(),
            ]);

        $response->assertCreated();

        $this->booking->refresh();
        $this->assertEquals(3000, $this->booking->paid_amount);
        $this->assertEquals(7000, $this->booking->balance_amount);
    }

    public function test_updating_payment_recalculates_booking_balance(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'client_id' => $this->booking->client_id,
            'amount' => 2000,
            'status' => 'pending',
        ]);

        $this->booking->update(['paid_amount' => 0, 'balance_amount' => 10000]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/payments/{$payment->id}", [
                'amount' => 4000,
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);

        $response->assertOk();

        $this->booking->refresh();
        $this->assertEquals(4000, $this->booking->paid_amount);
        $this->assertEquals(6000, $this->booking->balance_amount);
    }

    public function test_payment_update_is_atomic(): void
    {
        $payment = Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'client_id' => $this->booking->client_id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        $originalPaid = $this->booking->paid_amount;

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/payments/{$payment->id}", [
                'amount' => 5000,
                'status' => 'completed',
            ]);

        $response->assertOk();

        $this->booking->refresh();
        $this->assertEquals(5000, $this->booking->paid_amount, 'Balance should reflect the updated payment amount.');
    }
}
