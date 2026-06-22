<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Staff;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Venue;
use Database\Seeders\ShowcaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowcaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_at_least_ten_of_every_entity_on_its_own_tenant(): void
    {
        $this->seed(ShowcaseSeeder::class);

        $tenant = Tenant::where('slug', 'showcase')->first();
        $this->assertNotNull($tenant, 'Showcase tenant created');

        $tid = $tenant->id;
        $models = [
            Venue::class, Package::class, Client::class, Vendor::class, Staff::class,
            Inquiry::class, Quotation::class, Booking::class, Payment::class,
        ];

        foreach ($models as $model) {
            $count = $model::withoutGlobalScopes()->where('tenant_id', $tid)->count();
            $this->assertGreaterThanOrEqual(10, $count, "$model should have >= 10 showcase rows, got $count");
        }

        // Tasks are tenant-scoped too (>= 10).
        $taskCount = Task::withoutGlobalScopes()->where('tenant_id', $tid)->count();
        $this->assertGreaterThanOrEqual(10, $taskCount, "Task should have >= 10 showcase rows, got $taskCount");
    }

    public function test_it_creates_the_four_login_users_with_roles(): void
    {
        $this->seed(ShowcaseSeeder::class);

        $tenant = Tenant::where('slug', 'showcase')->firstOrFail();

        $expected = [
            'owner@showcase.eventpro.test' => 'tenant_owner',
            'admin@showcase.eventpro.test' => 'admin',
            'manager@showcase.eventpro.test' => 'manager',
            'staff@showcase.eventpro.test' => 'staff',
        ];

        foreach ($expected as $email => $role) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "$email exists");
            $this->assertSame($tenant->id, $user->tenant_id);
            $this->assertSame($role, $user->role);
            $this->assertTrue($user->hasRole($role), "$email has $role role");
        }
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(ShowcaseSeeder::class);
        $tenant = Tenant::where('slug', 'showcase')->firstOrFail();
        $first = Booking::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();

        $this->seed(ShowcaseSeeder::class);
        $second = Booking::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();

        $this->assertSame($first, $second, 'Re-running the seeder must not duplicate rows');
        $this->assertSame(1, Tenant::where('slug', 'showcase')->count(), 'Only one showcase tenant');
    }
}
