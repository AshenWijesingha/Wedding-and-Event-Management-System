<?php

namespace Tests\Feature\Api;

use App\Models\Inquiry;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_is_persisted_to_database(): void
    {
        $this->assertDatabaseCount('inquiries', 0);

        $response = $this->postJson('/api/v1/inquiries', [
            'name'    => 'Jane Smith',
            'email'   => 'jane@example.com',
            'phone'   => '0771234567',
            'message' => 'I would like to book a venue for my wedding.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('inquiries', 1);
        $this->assertDatabaseHas('inquiries', [
            'name'  => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_inquiry_response_contains_created_record(): void
    {
        $response = $this->postJson('/api/v1/inquiries', [
            'name'    => 'John Doe',
            'email'   => 'john@example.com',
            'message' => 'Interested in the grand ballroom.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('inquiry.name', 'John Doe')
            ->assertJsonPath('inquiry.email', 'john@example.com');

        $this->assertNotNull($response->json('inquiry.id'), 'Response should include the DB-assigned id.');
    }

    public function test_inquiry_with_venue_is_stored(): void
    {
        $tenant = Tenant::factory()->create();
        $venue  = Venue::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->postJson('/api/v1/inquiries', [
            'name'       => 'Alice',
            'email'      => 'alice@example.com',
            'message'    => 'Venue inquiry.',
            'venue_id'   => $venue->id,
            'event_date' => now()->addMonths(3)->toDateString(),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('inquiries', ['venue_id' => $venue->id]);
    }

    public function test_inquiry_validation_requires_name_email_message(): void
    {
        $response = $this->postJson('/api/v1/inquiries', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'message']);
    }
}
