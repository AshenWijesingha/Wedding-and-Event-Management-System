<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'staff',
            'is_active' => true,
        ];
    }

    /**
     * Assign the matching Spatie role (guard "web") after the user is created so
     * role-gated routes and hasRole() checks work in tests and seeders.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($user) {
            if ($user->role) {
                Role::findOrCreate($user->role, 'web');
                $user->assignRole($user->role);
            }
        });
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function manager(): static
    {
        return $this->state(fn () => ['role' => 'manager']);
    }

    public function client(): static
    {
        return $this->state(fn () => ['role' => 'client']);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
