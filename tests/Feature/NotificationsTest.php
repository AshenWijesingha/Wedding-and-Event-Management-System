<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Mail\NewInquiryMail;
use App\Mail\PaymentReceivedMail;
use App\Mail\QuotationSentMail;
use App\Mail\UserInvitedMail;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\StaffNotification;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->admin->tenant->update(['email' => 'inbox@tenant.test']);
    }

    private function client(): Client
    {
        return Client::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'email' => 'client@example.test',
        ]);
    }

    private function booking(array $attrs = []): Booking
    {
        $tid = $this->admin->tenant_id;

        return Booking::factory()->create(array_merge([
            'tenant_id' => $tid,
            'venue_id' => Venue::factory()->create(['tenant_id' => $tid])->id,
            'client_id' => $this->client()->id,
            'status' => 'tentative',
        ], $attrs));
    }

    public function test_sending_a_quotation_emails_the_client(): void
    {
        Mail::fake();
        $quotation = Quotation::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'client_id' => $this->client()->id,
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/quotations/{$quotation->id}/send")
            ->assertRedirect();

        Mail::assertQueued(QuotationSentMail::class, fn ($m) => $m->hasTo('client@example.test'));
    }

    public function test_confirming_a_booking_emails_client_and_notifies_staff(): void
    {
        Mail::fake();
        Notification::fake();
        $booking = $this->booking(['status' => 'tentative']);

        $this->actingAs($this->admin)
            ->post("/admin/bookings/{$booking->id}/confirm")
            ->assertRedirect();

        Mail::assertQueued(BookingConfirmedMail::class, fn ($m) => $m->hasTo('client@example.test'));
        Notification::assertSentTo($this->admin, StaffNotification::class);
    }

    public function test_manual_payment_emails_the_client(): void
    {
        Mail::fake();
        $booking = $this->booking(['status' => 'confirmed', 'total_amount' => 1000, 'balance_amount' => 1000]);

        app(PaymentService::class)->recordManual($booking, [
            'amount' => 500,
            'payment_method' => 'cash',
        ], $this->admin);

        Mail::assertQueued(PaymentReceivedMail::class, fn ($m) => $m->hasTo('client@example.test'));
    }

    public function test_inviting_a_user_emails_them(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'New Hire',
                'email' => 'hire@example.test',
                'role' => 'staff',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        Mail::assertQueued(UserInvitedMail::class, fn ($m) => $m->hasTo('hire@example.test'));
    }

    public function test_public_inquiry_emails_tenant_inbox_and_notifies_staff(): void
    {
        Mail::fake();
        Notification::fake();
        $this->admin->tenant->makeCurrent();

        $this->post('/inquiry', [
            'name' => 'Jane Public',
            'email' => 'jane@example.test',
            'message' => 'I would like a quote for a wedding.',
        ])->assertRedirect();

        Mail::assertQueued(NewInquiryMail::class, fn ($m) => $m->hasTo('inbox@tenant.test'));
        Notification::assertSentTo($this->admin, StaffNotification::class);
    }

    public function test_mail_failure_does_not_break_the_request(): void
    {
        // Force the mailer to throw; the request must still succeed (best-effort).
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));
        $this->admin->tenant->makeCurrent();

        $this->post('/inquiry', [
            'name' => 'Jane Public',
            'email' => 'jane@example.test',
            'message' => 'A message long enough to pass validation.',
        ])->assertRedirect();
    }

    public function test_admin_can_mark_a_notification_read(): void
    {
        $this->admin->notify(new StaffNotification('new_inquiry', 'Test message', '/admin/inquiries'));
        $id = $this->admin->notifications()->first()->id;

        $this->actingAs($this->admin)
            ->post('/admin/notifications/read', ['id' => $id])
            ->assertRedirect();

        $this->assertNotNull($this->admin->notifications()->first()->read_at);
    }

    public function test_unread_notifications_are_shared_to_inertia(): void
    {
        $this->admin->notify(new StaffNotification('new_inquiry', 'Hi', '/admin/inquiries'));
        $this->admin->tenant->setSetting('onboarding', ['seen' => true, 'dismissed' => true]);
        $this->admin->tenant->save();

        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertInertia(fn ($p) => $p->where('notifications.unread_count', 1)->has('notifications.items', 1));
    }
}
