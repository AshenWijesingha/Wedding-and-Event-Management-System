<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Venue;
use Carbon\Carbon;

class PricingService
{
    private SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Calculate full event price breakdown.
     */
    public function calculateEventPrice(array $params): array
    {
        $breakdown = [
            'venue' => 0,
            'package' => 0,
            'services' => [],
            'surcharges' => [],
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
        ];

        // Venue pricing
        if (!empty($params['venue_id'])) {
            $venue = Venue::findOrFail($params['venue_id']);
            $breakdown['venue'] = $this->calculateVenuePrice($venue, $params);
        }

        // Package pricing
        if (!empty($params['package_id'])) {
            $package = Package::findOrFail($params['package_id']);
            $breakdown['package'] = $this->calculatePackagePrice($package, $params['guest_count'] ?? 0);
        }

        // Services
        if (!empty($params['services'])) {
            foreach ($params['services'] as $service) {
                $breakdown['services'][] = $this->calculateServicePrice($service, $params['guest_count'] ?? 0);
            }
        }

        // Surcharges
        $breakdown['surcharges'] = $this->calculateSurcharges($params, $breakdown);

        // Calculate subtotal
        $servicesTotal = array_sum(array_column($breakdown['services'], 'total'));
        $surchargesTotal = array_sum(array_column($breakdown['surcharges'], 'amount'));
        
        $breakdown['subtotal'] = $breakdown['venue'] 
            + $breakdown['package'] 
            + $servicesTotal
            + $surchargesTotal;

        // Apply discount
        if (!empty($params['discount'])) {
            $breakdown['discount'] = $this->calculateDiscount($breakdown['subtotal'], $params['discount']);
        }

        // Tax
        $taxRate = $this->settings->get('pricing.tax_rate', 0);
        $taxableAmount = $breakdown['subtotal'] - $breakdown['discount'];
        $breakdown['tax'] = round($taxableAmount * ($taxRate / 100), 2);

        // Total
        $breakdown['total'] = round($taxableAmount + $breakdown['tax'], 2);

        return $breakdown;
    }

    /**
     * Calculate venue price.
     */
    private function calculateVenuePrice(Venue $venue, array $params): float
    {
        $price = (float) $venue->base_price;
        
        if (!empty($params['event_date'])) {
            $eventDate = Carbon::parse($params['event_date']);

            // Weekend surcharge
            if ($eventDate->isWeekend()) {
                $price += (float) $venue->weekend_surcharge;
            }
        }

        return $price;
    }

    /**
     * Calculate package price.
     */
    private function calculatePackagePrice(Package $package, int $guestCount): float
    {
        return $package->calculatePrice($guestCount);
    }

    /**
     * Calculate service price.
     */
    private function calculateServicePrice(array $service, int $guestCount): array
    {
        $total = 0;
        $quantity = $service['quantity'] ?? 1;
        $unitPrice = $service['unit_price'] ?? 0;

        if ($service['pricing_type'] === 'per_guest') {
            $total = $unitPrice * $guestCount * $quantity;
        } else {
            $total = $unitPrice * $quantity;
        }

        return [
            'name' => $service['name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $total,
        ];
    }

    /**
     * Calculate surcharges.
     */
    private function calculateSurcharges(array $params, array $breakdown): array
    {
        $surcharges = [];

        if (!empty($params['event_date'])) {
            $eventDate = Carbon::parse($params['event_date']);

            // Peak season surcharge
            if ($this->isPeakSeason($eventDate)) {
                $rate = $this->settings->get('pricing.peak_season_surcharge', 15);
                $baseAmount = $breakdown['venue'] + $breakdown['package'];
                
                if ($baseAmount > 0 && $rate > 0) {
                    $surcharges[] = [
                        'name' => 'Peak Season Surcharge',
                        'rate' => $rate,
                        'amount' => round($baseAmount * ($rate / 100), 2),
                    ];
                }
            }
        }

        return $surcharges;
    }

    /**
     * Check if date falls in peak season.
     */
    private function isPeakSeason(Carbon $date): bool
    {
        $peakMonths = $this->settings->get('pricing.peak_months', [12, 1, 2]);
        return in_array($date->month, $peakMonths);
    }

    /**
     * Calculate discount amount.
     */
    private function calculateDiscount(float $subtotal, array $discount): float
    {
        if ($discount['type'] === 'percentage') {
            return round($subtotal * ($discount['value'] / 100), 2);
        }
        
        return min((float) $discount['value'], $subtotal);
    }

    /**
     * Calculate deposit amount.
     */
    public function calculateDeposit(float $totalAmount): float
    {
        $percentage = $this->settings->get('booking.deposit_percentage', 25);
        return round($totalAmount * ($percentage / 100), 2);
    }

    /**
     * Generate payment schedule.
     */
    public function generatePaymentSchedule(float $totalAmount, string $eventDate): array
    {
        $schedule = $this->settings->getInstallmentSchedule();
        $payments = [];
        $eventDate = Carbon::parse($eventDate);

        foreach ($schedule as $index => $installment) {
            $amount = round($totalAmount * ($installment['percentage'] / 100), 2);
            
            // Calculate due date (rough estimate based on installment position)
            $dueDate = match($index) {
                0 => now()->addDays(7), // First payment due soon
                count($schedule) - 1 => $eventDate->copy()->subDays(7), // Final payment before event
                default => $eventDate->copy()->subDays(30 * (count($schedule) - $index)), // Middle payments
            };

            $payments[] = [
                'name' => $installment['name'],
                'percentage' => $installment['percentage'],
                'amount' => $amount,
                'due_date' => $dueDate->format('Y-m-d'),
            ];
        }

        return $payments;
    }
}
