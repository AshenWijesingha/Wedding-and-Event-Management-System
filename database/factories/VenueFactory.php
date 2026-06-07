<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VenueFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->words(3, true) . ' Hall';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->paragraph(),
            'capacity_min' => $min = $this->faker->numberBetween(20, 100),
            'capacity_max' => $min + $this->faker->numberBetween(50, 400),
            'base_price' => $this->faker->randomFloat(2, 500, 10000),
            'weekend_surcharge' => $this->faker->randomFloat(2, 0, 1000),
            'amenities' => $this->faker->randomElements(
                ['parking', 'wifi', 'catering', 'av_equipment', 'dance_floor', 'stage', 'valet'],
                $this->faker->numberBetween(2, 5)
            ),
            'images' => [],
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
