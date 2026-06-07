<?php

namespace Tests\Unit;

use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_valid_until_today_is_valid(): void
    {
        $quotation = new Quotation([
            'status' => 'sent',
            'valid_until' => Carbon::today(),
        ]);
        $quotation->setRawAttributes(['valid_until' => Carbon::today()->toDateString()]);

        $this->assertTrue($quotation->isValid(), 'Quotation expiring today should still be valid.');
    }

    public function test_quotation_valid_until_yesterday_is_invalid(): void
    {
        $quotation = new Quotation([
            'status' => 'sent',
        ]);
        $quotation->setRawAttributes(['valid_until' => Carbon::yesterday()->toDateString()]);

        $this->assertFalse($quotation->isValid(), 'Quotation expired yesterday should be invalid.');
    }

    public function test_quotation_valid_until_tomorrow_is_valid(): void
    {
        $quotation = new Quotation([
            'status' => 'sent',
        ]);
        $quotation->setRawAttributes(['valid_until' => Carbon::tomorrow()->toDateString()]);

        $this->assertTrue($quotation->isValid());
    }

    public function test_expired_status_quotation_is_invalid_regardless_of_date(): void
    {
        $quotation = new Quotation([
            'status' => 'expired',
        ]);
        $quotation->setRawAttributes([
            'status' => 'expired',
            'valid_until' => Carbon::tomorrow()->toDateString(),
        ]);

        $this->assertFalse($quotation->isValid());
    }

    public function test_null_valid_until_is_invalid(): void
    {
        $quotation = new Quotation(['status' => 'sent']);
        $quotation->setRawAttributes(['valid_until' => null]);

        $this->assertFalse($quotation->isValid());
    }

    public function test_scope_valid_includes_today(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $tenant->makeCurrent();

        \App\Models\Quotation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'sent',
            'valid_until' => Carbon::today(),
        ]);

        $valid = \App\Models\Quotation::valid()->count();
        $this->assertEquals(1, $valid, 'scopeValid should include quotations expiring today.');
    }

    public function test_scope_valid_excludes_yesterday(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $tenant->makeCurrent();

        \App\Models\Quotation::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'sent',
            'valid_until' => Carbon::yesterday(),
        ]);

        $valid = \App\Models\Quotation::valid()->count();
        $this->assertEquals(0, $valid, 'scopeValid should exclude quotations expired yesterday.');
    }
}
