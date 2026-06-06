<?php

namespace Tests\Unit;

use App\Console\Commands\SendPaymentReminders;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendPaymentRemindersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Client $client;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->tenant  = Tenant::factory()->create();
        $venue         = Venue::factory()->create(['tenant_id' => $this->tenant->id]);
        $user          = User::factory()->client()->create(['tenant_id' => $this->tenant->id]);
        $this->client  = Client::factory()->create(['tenant_id' => $this->tenant->id, 'user_id' => $user->id]);
        $this->booking = Booking::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'venue_id'  => $venue->id,
        ]);
    }

    public function test_reminder_sent_for_pending_overdue_payment(): void
    {
        Payment::factory()->create([
            'booking_id'  => $this->booking->id,
            'client_id'   => $this->client->id,
            'status'      => 'pending',
            'due_date'    => now()->subDay()->toDateString(),
            'reminder_sent_at' => null,
        ]);

        $this->artisan('payments:send-reminders')->assertSuccessful();

        Notification::assertSentTo($this->client, PaymentReminderNotification::class);
    }

    public function test_reminder_not_sent_for_completed_payment(): void
    {
        Payment::factory()->create([
            'booking_id'  => $this->booking->id,
            'client_id'   => $this->client->id,
            'status'      => 'completed',
            'due_date'    => now()->subDay()->toDateString(),
            'reminder_sent_at' => null,
        ]);

        $this->artisan('payments:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_reminder_not_sent_when_recently_reminded(): void
    {
        Payment::factory()->create([
            'booking_id'       => $this->booking->id,
            'client_id'        => $this->client->id,
            'status'           => 'pending',
            'due_date'         => now()->subDay()->toDateString(),
            'reminder_sent_at' => now()->subHours(12),
        ]);

        $this->artisan('payments:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_reminder_not_sent_for_far_future_payment(): void
    {
        Payment::factory()->create([
            'booking_id'       => $this->booking->id,
            'client_id'        => $this->client->id,
            'status'           => 'pending',
            'due_date'         => now()->addDays(30)->toDateString(),
            'reminder_sent_at' => null,
        ]);

        $this->artisan('payments:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_reminder_updates_reminder_sent_at_timestamp(): void
    {
        $payment = Payment::factory()->create([
            'booking_id'       => $this->booking->id,
            'client_id'        => $this->client->id,
            'status'           => 'pending',
            'due_date'         => now()->subDay()->toDateString(),
            'reminder_sent_at' => null,
        ]);

        $this->artisan('payments:send-reminders')->assertSuccessful();

        $this->assertNotNull($payment->fresh()->reminder_sent_at);
    }
}
