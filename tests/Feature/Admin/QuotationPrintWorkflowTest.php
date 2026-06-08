<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationPrintWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function quotationForAdmin(array $attrs = []): Quotation
    {
        $tenantId = $this->admin->tenant_id;
        $client = Client::factory()->create(['tenant_id' => $tenantId]);
        $venue = Venue::factory()->create(['tenant_id' => $tenantId]);

        return Quotation::factory()->create(array_merge([
            'tenant_id' => $tenantId,
            'client_id' => $client->id,
            'venue_id' => $venue->id,
        ], $attrs));
    }

    public function test_quotation_pdf_renders_item_name(): void
    {
        $quotation = $this->quotationForAdmin([
            'items' => [[
                'name' => 'Premium Catering',
                'description' => 'Three-course plated dinner',
                'quantity' => 2,
                'unit_price' => 1500,
                'total' => 3000,
            ]],
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/quotations/{$quotation->id}/pdf");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_quotation_print_view_returns_html_with_item_name(): void
    {
        $quotation = $this->quotationForAdmin([
            'items' => [[
                'name' => 'Premium Catering',
                'description' => 'Three-course plated dinner',
                'quantity' => 2,
                'unit_price' => 1500,
                'total' => 3000,
            ]],
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/quotations/{$quotation->id}/print");

        $response->assertOk();
        $response->assertSee('Premium Catering');
        $response->assertSee('window.print()', false);
    }

    public function test_print_view_renders_legacy_items_stored_without_name(): void
    {
        // Older rows stored only a "description" key — print must still show it.
        $quotation = $this->quotationForAdmin([
            'items' => [[
                'description' => 'Legacy Line Item',
                'quantity' => 1,
                'unit_price' => 500,
                'total' => 500,
            ]],
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/quotations/{$quotation->id}/print");

        $response->assertOk();
        $response->assertSee('Legacy Line Item');
    }

    public function test_inquiry_print_view_returns_html(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->admin->tenant_id]);
        $inquiry = Inquiry::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'client_id' => $client->id,
            'venue_id' => null,
            'package_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/inquiries/{$inquiry->id}/print");

        $response->assertOk();
        $response->assertSee('window.print()', false);
    }

    public function test_admin_can_send_then_accept_a_quotation(): void
    {
        $quotation = $this->quotationForAdmin(['status' => 'draft', 'sent_at' => null]);

        $this->actingAs($this->admin)->post("/admin/quotations/{$quotation->id}/send")
            ->assertRedirect();
        $quotation->refresh();
        $this->assertEquals('sent', $quotation->status);
        $this->assertNotNull($quotation->sent_at);

        $this->actingAs($this->admin)->post("/admin/quotations/{$quotation->id}/accept")
            ->assertRedirect();
        $quotation->refresh();
        $this->assertEquals('accepted', $quotation->status);
        $this->assertNotNull($quotation->accepted_at);
    }

    public function test_illegal_transition_is_rejected(): void
    {
        // Cannot accept a draft (must be sent/viewed first).
        $quotation = $this->quotationForAdmin(['status' => 'draft']);

        $this->actingAs($this->admin)->post("/admin/quotations/{$quotation->id}/accept");

        $this->assertEquals('draft', $quotation->fresh()->status);
    }

    public function test_create_form_prefills_from_inquiry(): void
    {
        $client = Client::factory()->create(['tenant_id' => $this->admin->tenant_id]);
        $venue = Venue::factory()->create(['tenant_id' => $this->admin->tenant_id]);
        $inquiry = Inquiry::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'client_id' => $client->id,
            'venue_id' => $venue->id,
            'package_id' => null,
            'guest_count' => 120,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/quotations/create?inquiry_id={$inquiry->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Quotations/Create')
            ->where('prefill.inquiry_id', $inquiry->id)
            ->where('prefill.client_id', $client->id)
            ->where('prefill.guest_count', 120)
        );
    }

    public function test_tenant_owner_can_create_quotation(): void
    {
        // Role gate previously allowed only admin|manager; tenant_owner is now permitted.
        $owner = User::factory()->tenantOwner()->create();
        $client = Client::factory()->create(['tenant_id' => $owner->tenant_id]);

        $payload = [
            'client_id' => $client->id,
            'items' => [[
                'name' => 'Setup Fee',
                'description' => 'On-site setup',
                'quantity' => 1,
                'unit_price' => 1000,
            ]],
            'tax_rate' => 10,
        ];

        $response = $this->actingAs($owner)->post('/admin/quotations', $payload);

        $response->assertRedirect(route('admin.quotations.index'));
        $this->assertDatabaseHas('quotations', ['client_id' => $client->id, 'status' => 'draft']);
    }
}
