<?php

namespace Tests\Feature\Admin;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The quotation builder lets staff pull pricing from the selected venue/package
 * and add line items sourced from vendors or entered as custom extras. Line
 * items carry an optional `type` and `vendor_id` inside the JSON `items` column.
 */
class QuotationBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function client(): Client
    {
        return Client::factory()->create(['tenant_id' => $this->admin->tenant_id]);
    }

    public function test_create_form_exposes_vendors_and_venue_pricing(): void
    {
        Venue::factory()->create(['tenant_id' => $this->admin->tenant_id, 'base_price' => 500000, 'status' => 'active']);
        Vendor::factory()->create(['tenant_id' => $this->admin->tenant_id, 'status' => 'active']);

        $this->actingAs($this->admin)
            ->get('/admin/quotations/create')
            ->assertInertia(fn ($page) => $page
                ->component('Quotations/Create')
                ->has('venues.0.base_price')
                ->has('vendors.0.base_rate'));
    }

    public function test_a_vendor_line_item_persists_its_type_and_vendor_id(): void
    {
        $client = $this->client();
        $vendor = Vendor::factory()->create(['tenant_id' => $this->admin->tenant_id, 'base_rate' => 75000]);

        $this->actingAs($this->admin)->post('/admin/quotations', [
            'client_id' => $client->id,
            'items' => [
                ['name' => 'Venue Hire — Grand', 'quantity' => 1, 'unit_price' => 500000, 'type' => 'venue'],
                ['name' => $vendor->name, 'quantity' => 1, 'unit_price' => 75000, 'type' => 'vendor', 'vendor_id' => $vendor->id],
                ['name' => 'Surprise dance', 'quantity' => 1, 'unit_price' => 120000, 'type' => 'custom'],
            ],
            'tax_rate' => 10,
        ])->assertRedirect(route('admin.quotations.index'));

        $quotation = Quotation::where('client_id', $client->id)->firstOrFail();
        $byType = collect($quotation->items)->keyBy('type');

        $this->assertSame('venue', $byType['venue']['type']);
        $this->assertSame($vendor->id, $byType['vendor']['vendor_id']);
        $this->assertNull($byType['custom']['vendor_id']);
        $this->assertSame('custom', $byType['custom']['type']);
    }

    public function test_item_type_defaults_to_custom_when_omitted(): void
    {
        $client = $this->client();

        $this->actingAs($this->admin)->post('/admin/quotations', [
            'client_id' => $client->id,
            'items' => [['name' => 'Misc', 'quantity' => 1, 'unit_price' => 1000]],
        ])->assertRedirect(route('admin.quotations.index'));

        $quotation = Quotation::where('client_id', $client->id)->firstOrFail();
        $this->assertSame('custom', $quotation->items[0]['type']);
    }

    public function test_vendor_id_from_another_tenant_is_rejected(): void
    {
        $client = $this->client();

        // A vendor belonging to a different tenant must not be referenceable.
        $otherTenant = Tenant::factory()->create();
        $foreignVendor = Vendor::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->actingAs($this->admin)->post('/admin/quotations', [
            'client_id' => $client->id,
            'items' => [
                ['name' => 'Sneaky', 'quantity' => 1, 'unit_price' => 1000, 'type' => 'vendor', 'vendor_id' => $foreignVendor->id],
            ],
        ])->assertSessionHasErrors('items.0.vendor_id');
    }
}
