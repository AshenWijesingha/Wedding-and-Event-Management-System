<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'tenant_id'    => Tenant::factory(),
            'name'         => $name,
            'slug'         => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(100, 999),
            'category'     => $this->faker->randomElement(['photographer', 'caterer', 'florist', 'music', 'decor', 'transport', 'videographer']),
            'contact_name' => $this->faker->name(),
            'email'        => $this->faker->companyEmail(),
            'phone'        => $this->faker->phoneNumber(),
            'website'      => $this->faker->url(),
            'description'  => $this->faker->paragraph(),
            'base_rate'    => $this->faker->randomFloat(2, 200, 5000),
            'rate_type'    => $this->faker->randomElement(['fixed', 'hourly', 'per_event']),
            'services'     => $this->faker->randomElements(['photos', 'video', 'editing', 'albums', 'delivery'], 3),
            'status'       => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
