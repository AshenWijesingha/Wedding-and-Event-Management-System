<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationFactory extends Factory
{
    public function definition(): array
    {
        $statuses = ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'cancelled'];

        $subtotal = $this->faker->numberBetween(10000, 80000);
        $discount = $this->faker->numberBetween(0, (int) ($subtotal * 0.15));
        $taxable = $subtotal - $discount;
        $tax = (int) ($taxable * 0.1);
        $total = $taxable + $tax;

        $items = [];
        $itemCount = $this->faker->numberBetween(3, 8);
        for ($i = 0; $i < $itemCount; $i++) {
            $qty = $this->faker->numberBetween(1, 10);
            $price = $this->faker->numberBetween(500, 5000);
            $items[] = [
                'name' => $this->faker->randomElement(['Venue Hire', 'Catering', 'Decoration', 'Photography', 'Music', 'Flowers', 'Cake', 'Transport']),
                'description' => $this->faker->sentence(),
                'quantity' => $qty,
                'unit_price' => $price,
                'total' => $qty * $price,
            ];
        }

        return [
            'tenant_id' => Tenant::factory(),
            'quotation_number' => 'QT' . date('Y') . $this->faker->unique()->numerify('####'),
            'client_id' => Client::factory(),
            'venue_id' => Venue::factory(),
            'inquiry_id' => null,
            'booking_id' => null,
            'event_date' => $this->faker->dateTimeBetween('+1 month', '+12 months'),
            'guest_count' => $this->faker->numberBetween(20, 500),
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'valid_until' => $this->faker->dateTimeBetween('+7 days', '+60 days'),
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->optional(0.5)->paragraph(),
            'terms_and_conditions' => $this->faker->optional(0.7)->paragraphs(2, true),
            'prepared_by' => null,
            'sent_at' => null,
            'viewed_at' => null,
            'accepted_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => 'sent',
            'sent_at' => now()->subDays($this->faker->numberBetween(1, 14)),
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => 'accepted',
            'sent_at' => now()->subDays(10),
            'viewed_at' => now()->subDays(8),
            'accepted_at' => now()->subDays(5),
        ]);
    }
}
