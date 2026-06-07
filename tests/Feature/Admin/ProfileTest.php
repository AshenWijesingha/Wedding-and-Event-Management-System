<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::factory()->create();
        $this->user = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
    }

    public function test_profile_page_renders(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/profile')
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Profile/Edit')->has('profile'));
    }

    public function test_user_can_update_profile(): void
    {
        $response = $this->actingAs($this->user)->patch('/admin/profile', [
            'name'  => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1 555-9999',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id'    => $this->user->id,
            'name'  => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1 555-9999',
        ]);
    }

    public function test_user_can_change_password(): void
    {
        $response = $this->actingAs($this->user)->put('/admin/profile/password', [
            'current_password'      => 'password',
            'password'              => 'new-secret-pass-123',
            'password_confirmation' => 'new-secret-pass-123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-secret-pass-123', $this->user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->user)->put('/admin/profile/password', [
            'current_password'      => 'wrong-password',
            'password'              => 'new-secret-pass-123',
            'password_confirmation' => 'new-secret-pass-123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }
}
