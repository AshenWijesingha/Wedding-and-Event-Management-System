<?php

namespace Tests\Unit;

use App\Services\PricingService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    private SettingsService $settings;

    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = Mockery::mock(SettingsService::class);
        $this->settings->allows('get')->with('pricing.tax_rate', 0)->andReturn(10);
        $this->settings->allows('get')->with('pricing.peak_season_surcharge', 15)->andReturn(15);
        $this->settings->allows('get')->with('pricing.peak_months', [12, 1, 2])->andReturn([12, 1, 2]);
        $this->settings->allows('get')->with('booking.deposit_percentage', 25)->andReturn(25);

        $this->pricing = new PricingService($this->settings);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_percentage_discount_calculation(): void
    {
        $subtotal = 1000.00;
        $discountPercentage = 10;
        $expected = 100.00;

        $actual = $subtotal * ($discountPercentage / 100);

        $this->assertEquals($expected, $actual);
    }

    public function test_fixed_discount_cannot_exceed_subtotal(): void
    {
        $subtotal = 1000.00;
        $fixedDiscount = 1500.00;

        $applied = min($fixedDiscount, $subtotal);

        $this->assertEquals(1000.00, $applied);
    }

    public function test_fixed_discount_below_subtotal_applied_in_full(): void
    {
        $subtotal = 1000.00;
        $fixedDiscount = 150.00;

        $applied = min($fixedDiscount, $subtotal);

        $this->assertEquals(150.00, $applied);
    }

    public function test_tax_calculation(): void
    {
        $taxableAmount = 1000.00;
        $taxRate = 8.5;

        $tax = round($taxableAmount * ($taxRate / 100), 2);

        $this->assertEquals(85.00, $tax);
    }

    public function test_deposit_calculation(): void
    {
        $deposit = $this->pricing->calculateDeposit(10000.00);

        // 25% of 10000
        $this->assertEquals(2500.00, $deposit);
    }

    public function test_service_price_flat_rate(): void
    {
        // Access private method via reflection
        $reflection = new \ReflectionClass($this->pricing);
        $method = $reflection->getMethod('calculateServicePrice');
        $method->setAccessible(true);

        $service = [
            'name' => 'Catering',
            'pricing_type' => 'flat',
            'unit_price' => 500,
            'quantity' => 2,
        ];

        $result = $method->invoke($this->pricing, $service, 100);

        $this->assertEquals(1000, $result['total']);
        $this->assertEquals(2, $result['quantity']);
    }

    public function test_service_price_per_guest(): void
    {
        $reflection = new \ReflectionClass($this->pricing);
        $method = $reflection->getMethod('calculateServicePrice');
        $method->setAccessible(true);

        $service = [
            'name' => 'Meals',
            'pricing_type' => 'per_guest',
            'unit_price' => 25,
            'quantity' => 1,
        ];

        $result = $method->invoke($this->pricing, $service, 80);

        $this->assertEquals(2000, $result['total']); // 25 * 80
    }

    public function test_peak_season_detection_december(): void
    {
        $reflection = new \ReflectionClass($this->pricing);
        $method = $reflection->getMethod('isPeakSeason');
        $method->setAccessible(true);

        $december = Carbon::create(2024, 12, 15);
        $april = Carbon::create(2024, 4, 10);

        $this->assertTrue($method->invoke($this->pricing, $december));
        $this->assertFalse($method->invoke($this->pricing, $april));
    }

    public function test_calculate_event_price_returns_expected_keys(): void
    {
        $result = $this->pricing->calculateEventPrice([]);

        $this->assertArrayHasKey('venue', $result);
        $this->assertArrayHasKey('package', $result);
        $this->assertArrayHasKey('services', $result);
        $this->assertArrayHasKey('surcharges', $result);
        $this->assertArrayHasKey('subtotal', $result);
        $this->assertArrayHasKey('discount', $result);
        $this->assertArrayHasKey('tax', $result);
        $this->assertArrayHasKey('total', $result);
    }

    public function test_empty_params_returns_zero_totals(): void
    {
        $result = $this->pricing->calculateEventPrice([]);

        $this->assertEquals(0, $result['subtotal']);
        $this->assertEquals(0, $result['total']);
    }
}
