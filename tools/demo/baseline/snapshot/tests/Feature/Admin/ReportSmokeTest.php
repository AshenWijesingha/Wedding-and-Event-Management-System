<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::factory()->create();
        $this->admin = User::factory()->admin()->create(['tenant_id' => $tenant->id]);

        // Seed a booking + payment so monthly aggregation has data to group.
        $venue = Venue::factory()->create(['tenant_id' => $tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);
        $booking = Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'venue_id' => $venue->id,
            'client_id' => $client->id,
            'event_date' => now()->toDateString(),
            'status' => 'confirmed',
        ]);
        Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'status' => 'completed',
            'payment_date' => now()->toDateString(),
        ]);
    }

    public function test_report_pages_render(): void
    {
        foreach (['/admin/reports', '/admin/reports/revenue', '/admin/reports/bookings', '/admin/reports/occupancy'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertStatus(200);
        }
    }

    public function test_report_exports_download_csv(): void
    {
        foreach (['revenue', 'bookings', 'occupancy'] as $report) {
            $response = $this->actingAs($this->admin)->get("/admin/reports/{$report}/export?year=" . now()->year);
            $response->assertStatus(200);
            $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        }
    }

    public function test_reports_accept_custom_date_range(): void
    {
        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();

        foreach (['/admin/reports', '/admin/reports/revenue', '/admin/reports/bookings', '/admin/reports/occupancy'] as $url) {
            $this->actingAs($this->admin)->get("{$url}?from={$from}&to={$to}")->assertStatus(200);
        }
    }

    public function test_report_pdfs_download(): void
    {
        foreach (['revenue', 'bookings', 'occupancy'] as $report) {
            $response = $this->actingAs($this->admin)->get("/admin/reports/{$report}/pdf?year=" . now()->year);
            $response->assertStatus(200);
            $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        }
    }
}
