<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Starter', 'Professional', 'Enterprise']) . ' ' . $this->faker->unique()->numberBetween(1, 9999);

        return [
            'name'          => $name,
            'slug'          => Str::slug($name),
            'description'   => $this->faker->sentence(),
            'price_monthly' => $this->faker->randomFloat(2, 0, 199),
            'price_yearly'  => $this->faker->randomFloat(2, 0, 1999),
            'features'      => ['Reports', 'Branding'],
            'limits'        => ['users' => 10],
            'is_active'     => true,
            'sort_order'    => 0,
        ];
    }
}
