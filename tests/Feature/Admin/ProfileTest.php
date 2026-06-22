<?php

namespace Tests\Feature\Admin;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
        $response = $this->actingAs($this->user)->post('/admin/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1 555-9999',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '+1 555-9999',
        ]);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->user)->post('/admin/profile', [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => UploadedFile::fake()->create('me.jpg', 80, 'image/jpeg'),
        ]);

        $response->assertSessionHasNoErrors();
        $path = $this->user->fresh()->avatar;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_user_can_remove_avatar(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->create('old.jpg', 80, 'image/jpeg')->store('avatars', 'public');
        $this->user->update(['avatar' => $path]);

        $this->actingAs($this->user)->post('/admin/profile', [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'remove_avatar' => true,
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->user->fresh()->avatar);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_changing_email_marks_it_unverified(): void
    {
        $this->assertNotNull($this->user->email_verified_at);

        $this->actingAs($this->user)->post('/admin/profile', [
            'name' => $this->user->name,
            'email' => 'changed@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->user->fresh()->email_verified_at);
    }

    public function test_user_can_resend_verification_link(): void
    {
        Notification::fake();
        $unverified = User::factory()->admin()->unverified()->create([
            'tenant_id' => $this->user->tenant_id,
        ]);

        $this->actingAs($unverified)
            ->post('/admin/profile/verification-notification')
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($unverified, VerifyEmail::class);
    }

    public function test_user_can_change_password(): void
    {
        $response = $this->actingAs($this->user)->put('/admin/profile/password', [
            'current_password' => 'password',
            'password' => 'new-secret-pass-123',
            'password_confirmation' => 'new-secret-pass-123',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-secret-pass-123', $this->user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->user)->put('/admin/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-pass-123',
            'password_confirmation' => 'new-secret-pass-123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }
}
