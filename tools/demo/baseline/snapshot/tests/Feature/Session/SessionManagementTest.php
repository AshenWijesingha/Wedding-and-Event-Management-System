<?php

namespace Tests\Feature\Session;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->tenant = Tenant::factory()->create();
        // Device management requires the database session driver to enumerate sessions.
        config(['session.driver' => 'database']);
    }

    private function admin(string $password = 'password'): User
    {
        return User::factory()
            ->state(['role' => 'admin', 'password' => Hash::make($password)])
            ->create(['tenant_id' => $this->tenant->id]);
    }

    private function insertSession(string $id, int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120',
            'payload' => base64_encode('x'),
            'last_activity' => time(),
        ]);
    }

    public function test_lists_only_own_sessions(): void
    {
        $user = $this->admin();
        $other = $this->admin();
        $this->insertSession('sess-a', $user->id);
        $this->insertSession('sess-b', $user->id);
        $this->insertSession('sess-x', $other->id);

        $this->actingAs($user)->get('/admin/profile/sessions')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Settings/Sessions')
                ->where('supported', true)
                ->has('sessions', 2));
    }

    public function test_revoke_requires_password_confirmation(): void
    {
        $user = $this->admin();
        $this->insertSession('sess-a', $user->id);

        $this->actingAs($user)->delete('/admin/profile/sessions/sess-a')
            ->assertRedirect(route('password.confirm'));

        $this->assertDatabaseHas('sessions', ['id' => 'sess-a']);
    }

    public function test_revoke_other_session_deletes_the_row(): void
    {
        $user = $this->admin();
        $this->insertSession('sess-a', $user->id);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete('/admin/profile/sessions/sess-a')
            ->assertRedirect();

        $this->assertDatabaseMissing('sessions', ['id' => 'sess-a']);
    }

    public function test_cannot_revoke_another_users_session(): void
    {
        $user = $this->admin();
        $other = $this->admin();
        $this->insertSession('sess-x', $other->id);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete('/admin/profile/sessions/sess-x')
            ->assertNotFound();

        $this->assertDatabaseHas('sessions', ['id' => 'sess-x']);
    }

    public function test_password_change_revokes_other_sessions(): void
    {
        $user = $this->admin('old-pass-123');
        $this->insertSession('current-ish', $user->id);
        $this->insertSession('other-device', $user->id);

        $this->actingAs($user)->put('/admin/profile/password', [
            'current_password' => 'old-pass-123',
            'password' => 'new-pass-456',
            'password_confirmation' => 'new-pass-456',
        ])->assertRedirect();

        // Other-device rows are gone (the request's own session id is not one of these).
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertDatabaseMissing('sessions', ['id' => 'current-ish']);
    }
}
