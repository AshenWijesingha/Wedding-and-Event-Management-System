<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'primary_color' => $this->faker->hexColor(),
            'status' => 'active',
            'settings' => [],
        ];
    }

    public function withPlan(string $slug = 'professional'): static
    {
        return $this->state(fn () => [
            'plan_id' => Plan::where('slug', $slug)->value('id'),
        ]);
    }

    public function trial(): static
    {
        return $this->state(fn () => [
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }
}
