<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $total = $this->faker->randomFloat(2, 5000, 50000);
        $paid = $this->faker->randomFloat(2, 0, $total);

        return [
            'tenant_id' => Tenant::factory(),
            'venue_id' => Venue::factory(),
            'client_id' => Client::factory(),
            'booking_number' => Booking::generateBookingNumber(),
            'event_type' => $this->faker->randomElement(['wedding', 'corporate', 'birthday', 'conference', 'other']),
            'event_date' => $this->faker->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d'),
            'setup_time' => '08:00',
            'event_start_time' => '10:00',
            'event_end_time' => '22:00',
            'guest_count' => $this->faker->numberBetween(20, 500),
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => $total - $paid,
            'status' => $this->faker->randomElement(['pending', 'tentative', 'confirmed']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
