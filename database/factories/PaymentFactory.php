<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $methods = ['cash', 'bank_transfer', 'credit_card', 'cheque', 'online'];
        $installments = ['Deposit', 'First Installment', 'Second Installment', 'Final Payment', 'Balance'];
        $statuses = ['pending', 'completed', 'failed', 'refunded'];

        return [
            'payment_number' => 'PAY' . date('Y') . $this->faker->unique()->numerify('####'),
            'booking_id' => Booking::factory(),
            'client_id' => Client::factory(),
            'installment_name' => $this->faker->randomElement($installments),
            'amount' => $this->faker->numberBetween(1000, 30000),
            'payment_method' => $this->faker->randomElement($methods),
            'payment_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'reference_number' => $this->faker->optional(0.7)->numerify('REF########'),
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'received_by' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'payment_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'reference_number' => $this->faker->numerify('REF########'),
        ]);
    }

    public function pending(): static
    {
        return $this->state([
            'status' => 'pending',
            'payment_date' => null,
            'reference_number' => null,
        ]);
    }

    public function deposit(): static
    {
        return $this->state(['installment_name' => 'Deposit']);
    }
}
