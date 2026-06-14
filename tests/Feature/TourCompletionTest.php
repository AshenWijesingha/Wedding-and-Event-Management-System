<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourCompletionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $tenant = Tenant::factory()->create();
        // Skip the onboarding redirect so Inertia admin pages render.
        $tenant->setSetting('onboarding', ['seen' => true, 'dismissed' => true]);

        return User::factory()->state(['role' => 'admin'])->create(['tenant_id' => $tenant->id]);
    }

    public function test_completing_a_tour_persists_the_key(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/tours/complete', ['key' => 'dashboard'])
            ->assertNoContent();

        $this->assertSame(['dashboard'], $admin->fresh()->preferences['completed_tours']);
    }

    public function test_completion_is_idempotent(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/tours/complete', ['key' => 'bookings'])->assertNoContent();
        $this->actingAs($admin)->post('/tours/complete', ['key' => 'bookings'])->assertNoContent();

        $this->assertSame(['bookings'], $admin->fresh()->preferences['completed_tours']);
    }

    public function test_unknown_key_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/tours/complete', ['key' => 'not-a-real-tour'])
            ->assertSessionHasErrors('key');

        $this->assertNull($admin->fresh()->preferences['completed_tours'] ?? null);
    }

    public function test_guest_cannot_complete_a_tour(): void
    {
        $this->post('/tours/complete', ['key' => 'dashboard'])->assertRedirect('/login');
    }

    public function test_completed_tours_are_shared_to_inertia(): void
    {
        $admin = $this->admin();
        $admin->update(['preferences' => ['completed_tours' => ['dashboard', 'payments']]]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertInertia(fn ($p) => $p->where('completedTours', ['dashboard', 'payments']));
    }
}
