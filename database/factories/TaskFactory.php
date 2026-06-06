<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'   => Tenant::factory(),
            'title'       => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'priority'    => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status'      => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'due_date'    => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'completed_at' => now()]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status'   => 'pending',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }
}
