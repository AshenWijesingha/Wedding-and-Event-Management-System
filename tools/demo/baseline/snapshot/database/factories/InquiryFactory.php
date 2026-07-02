<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class InquiryFactory extends Factory
{
    public function definition(): array
    {
        $eventTypes = ['wedding', 'corporate', 'birthday', 'anniversary', 'gala', 'conference'];
        $sources = ['website', 'referral', 'social_media', 'phone', 'walk_in', 'email'];
        $statuses = ['pending', 'contacted', 'qualified', 'proposal_sent', 'converted', 'closed'];

        $preferredDate = $this->faker->dateTimeBetween('+1 month', '+18 months');

        return [
            'tenant_id' => Tenant::factory(),
            'inquiry_number' => 'INQ' . date('Y') . $this->faker->unique()->numerify('####'),
            'client_id' => Client::factory(),
            'venue_id' => Venue::factory(),
            'package_id' => Package::factory(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'preferred_date' => $preferredDate,
            'alternate_date' => $this->faker->optional(0.6)->dateTimeBetween('+1 month', '+18 months'),
            'guest_count' => $this->faker->numberBetween(20, 500),
            'budget_range_min' => $this->faker->numberBetween(5000, 20000),
            'budget_range_max' => $this->faker->numberBetween(20001, 100000),
            'message' => $this->faker->paragraphs(2, true),
            'source' => $this->faker->randomElement($sources),
            'status' => $this->faker->randomElement($statuses),
            'assigned_to' => null,
            'notes' => $this->faker->optional(0.4)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function converted(): static
    {
        return $this->state(['status' => 'converted']);
    }

    public function qualified(): static
    {
        return $this->state(['status' => 'qualified']);
    }
}
