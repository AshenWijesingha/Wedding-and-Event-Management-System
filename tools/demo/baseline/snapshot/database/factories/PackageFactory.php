<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PackageFactory extends Factory
{
    public function definition(): array
    {
        $names = ['Silver', 'Gold', 'Platinum', 'Diamond', 'Premium', 'Classic', 'Deluxe', 'Elite'];
        $name = $this->faker->randomElement($names) . ' Package';

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->paragraph(),
            'base_price' => $this->faker->randomFloat(2, 1000, 20000),
            'min_guests' => $min = $this->faker->numberBetween(20, 50),
            'max_guests' => $min + $this->faker->numberBetween(50, 300),
            'guest_pricing' => [
                ['from' => $min, 'to' => $min + 50, 'price_per_guest' => 50],
                ['from' => $min + 51, 'to' => $min + 150, 'price_per_guest' => 45],
            ],
            'included_services' => $this->faker->randomElements(
                ['catering', 'decoration', 'photography', 'music', 'cake', 'flowers', 'invitations'],
                $this->faker->numberBetween(2, 5)
            ),
            'status' => 'active',
            'approval_status' => 'approved',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['approval_status' => 'draft']);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['approval_status' => 'pending', 'submitted_at' => now()]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['approval_status' => 'approved', 'reviewed_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['approval_status' => 'rejected', 'review_notes' => 'Please revise.']);
    }
}
