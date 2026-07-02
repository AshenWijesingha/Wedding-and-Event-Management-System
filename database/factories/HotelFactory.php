<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HotelFactory extends Factory
{
    public function definition(): array
    {
        $name = ucwords($this->faker->unique()->company()).' Hotel';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'city' => $this->faker->city(),
            'address' => $this->faker->streetAddress(),
            'description' => $this->faker->paragraph(),
            'star_rating' => $this->faker->numberBetween(3, 5),
            'images' => [],
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
