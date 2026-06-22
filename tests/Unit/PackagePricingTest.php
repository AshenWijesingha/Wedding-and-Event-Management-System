<?php

namespace Tests\Unit;

use App\Models\Package;
use PHPUnit\Framework\TestCase;

class PackagePricingTest extends TestCase
{
    private function makePackage(float $basePrice, ?array $guestPricing = null, int $minGuests = 0): Package
    {
        $pkg = new Package;
        $pkg->base_price = $basePrice;
        $pkg->min_guests = $minGuests;
        $pkg->guest_pricing = $guestPricing;

        return $pkg;
    }

    public function test_base_price_only_when_no_guest_pricing(): void
    {
        $pkg = $this->makePackage(5000.00);
        $this->assertEquals(5000.00, $pkg->calculatePrice(100));
    }

    public function test_base_price_returned_when_guest_count_in_tier(): void
    {
        $pkg = $this->makePackage(5000.00, [
            ['from' => 1, 'to' => 100, 'price_per_guest' => 10],
        ], 1);

        $price = $pkg->calculatePrice(50);
        $this->assertEquals(5000.00 + (50 - 1) * 10, $price);
    }

    public function test_base_price_returned_when_guest_count_outside_all_tiers(): void
    {
        $pkg = $this->makePackage(5000.00, [
            ['from' => 50, 'to' => 100, 'price_per_guest' => 20],
        ], 50);

        $price = $pkg->calculatePrice(10);
        $this->assertEquals(5000.00, $price, 'Guest count below tier range should return base price only.');
    }

    public function test_correct_tier_applied_for_boundary_guest_count(): void
    {
        $pkg = $this->makePackage(5000.00, [
            ['from' => 1, 'to' => 50, 'price_per_guest' => 10],
            ['from' => 51, 'to' => 200, 'price_per_guest' => 8],
        ], 1);

        $priceAt50 = $pkg->calculatePrice(50);
        $priceAt51 = $pkg->calculatePrice(51);

        $this->assertEquals(5000.00 + (50 - 1) * 10, $priceAt50, 'Exactly 50 guests: tier 1 applies.');
        $this->assertEquals(5000.00 + (51 - 1) * 8, $priceAt51, 'Exactly 51 guests: tier 2 applies.');
    }

    public function test_zero_guest_pricing_array_returns_base_price(): void
    {
        $pkg = $this->makePackage(3000.00, []);
        $this->assertEquals(3000.00, $pkg->calculatePrice(75));
    }
}
