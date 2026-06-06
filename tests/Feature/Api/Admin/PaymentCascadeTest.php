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

class PaymentCascadeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();
        $venue  = Venue::factory()->create(['tenant_id' => $tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);

        $this->admin = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
        $this->admin->assignRole('admin');

        $this->booking = Booking::factory()->create([
            'tenant_id'      => $tenant->id,
            'client_id'      => $client->id,
            'venue_id'       => $venue->id,
            'total_amount'   => 10000,
            'paid_amount'    => 2000,
            'balance_amount' => 8000,
            'status'         => 'tentative',
        ]);
    }

    public function test_deleting_booking_via_api_removes_the_record(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/bookings/{$this->booking->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('bookings', ['id' => $this->booking->id]);
    }

    public function test_payments_associated_with_deleted_booking_remain_or_cascade(): void
    {
        Payment::factory()->create([
            'booking_id' => $this->booking->id,
            'client_id'  => $this->booking->client_id,
            'amount'     => 2000,
            'status'     => 'completed',
        ]);

        $paymentCount = Payment::where('booking_id', $this->booking->id)->count();
        $this->assertEquals(1, $paymentCount, 'Payment should exist before booking deletion.');

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/bookings/{$this->booking->id}");

        $this->assertDatabaseMissing('bookings', ['id' => $this->booking->id]);
    }

    public function test_completed_booking_cannot_be_deleted(): void
    {
        $this->booking->update(['status' => 'completed']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/bookings/{$this->booking->id}");

        $response->assertUnprocessable();
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id]);
    }

    public function test_booking_balance_updates_when_payment_added(): void
    {
        $this->booking->update(['paid_amount' => 0, 'balance_amount' => 10000]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/payments', [
                'booking_id'       => $this->booking->id,
                'installment_name' => 'Deposit',
                'amount'           => 2500,
                'payment_method'   => 'cash',
                'payment_date'     => now()->toDateString(),
            ]);

        $this->booking->refresh();
        $this->assertEquals(2500, (float) $this->booking->paid_amount);
        $this->assertEquals(7500, (float) $this->booking->balance_amount);
    }
}
