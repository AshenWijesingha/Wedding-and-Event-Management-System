<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /** All roles in the system, ordered most to least privileged. */
    private const ROLES = ['super_admin', 'admin', 'manager', 'staff', 'client'];

    public function index(Request $request): Response
    {
        $actor = $request->user();

        $users = $this->userQuery($actor)
            ->with('tenant:id,name')
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            )
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->role,
                'is_active'  => $u->is_active,
                'tenant'     => $u->tenant?->name,
                'avatar_url' => $u->avatar_url,
                'verified'   => ! is_null($u->email_verified_at),
            ]);

        return Inertia::render('Users/Index', [
            'users'      => $users,
            'filters'    => $request->only(['search', 'role', 'status']),
            'roles'      => $this->assignableRoles($actor),
            'systemWide' => $actor->isSuperAdmin(),
            'stats'      => [
                'total'    => $this->userQuery($actor)->count(),
                'active'   => $this->userQuery($actor)->where('is_active', true)->count(),
                'inactive' => $this->userQuery($actor)->where('is_active', false)->count(),
                'byRole'   => $this->userQuery($actor)->selectRaw('role, COUNT(*) as count')->groupBy('role')->pluck('count', 'role'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $request->user();

        return Inertia::render('Users/Create', [
            'roles'   => $this->assignableRoles($actor),
            'tenants' => $this->tenantsFor($actor),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();

        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role'      => ['required', Rule::in($this->assignableRoles($actor))],
            'phone'     => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'password'  => ['required', 'confirmed', Password::defaults()],
        ];

        // Only a super admin chooses the tenant; an admin is pinned to their own.
        if ($actor->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules);

        $tenantId = $actor->isSuperAdmin() ? $validated['tenant_id'] : $actor->tenant_id;

        $user = new User();
        $user->forceFill([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'tenant_id' => $tenantId,
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'password'  => $validated['password'],
        ])->save();

        $user->syncRoles([$validated['role']]);

        return redirect('/admin/users')->with('success', 'User created.');
    }

    public function edit(Request $request, int $id): Response
    {
        $actor = $request->user();
        $user  = $this->findManageable($actor, $id);

        return Inertia::render('Users/Edit', [
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role,
                'tenant_id' => $user->tenant_id,
                'phone'     => $user->phone,
                'is_active' => $user->is_active,
            ],
            'roles'   => $this->assignableRoles($actor),
            'tenants' => $this->tenantsFor($actor),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        $user  = $this->findManageable($actor, $id);

        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'      => ['required', Rule::in($this->assignableRoles($actor))],
            'phone'     => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];

        if ($actor->isSuperAdmin()) {
            $rules['tenant_id'] = ['required', 'exists:tenants,id'];
        }

        $validated = $request->validate($rules);

        // An actor must not lock themselves out of their own account.
        if ($user->id === $actor->id) {
            if ($validated['role'] !== $user->role) {
                return back()->with('error', 'You cannot change your own role.');
            }
            if (! $request->boolean('is_active')) {
                return back()->with('error', 'You cannot deactivate your own account.');
            }
        }

        $user->forceFill([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'tenant_id' => $actor->isSuperAdmin() ? $validated['tenant_id'] : $user->tenant_id,
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        $user->syncRoles([$validated['role']]);

        return redirect('/admin/users')->with('success', 'User updated.');
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        $user  = $this->findManageable($actor, $id);

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => $request->password]);

        return back()->with('success', "Password reset for {$user->name}.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        $user  = $this->findManageable($actor, $id);

        if ($user->id === $actor->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect('/admin/users')->with('success', 'User deleted.');
    }

    /**
     * Base query for users the actor is allowed to see.
     * Super admins manage every tenant; admins are limited to their own tenant
     * via the BelongsToTenant global scope (SetCurrentTenant sets the current tenant).
     */
    private function userQuery(User $actor): Builder
    {
        return $actor->isSuperAdmin() ? User::withoutTenantScope() : User::query();
    }

    /**
     * Load a target user within the actor's scope, enforcing privilege limits.
     * Cross-tenant ids 404 for admins; super_admin targets are off-limits to non-super actors.
     */
    private function findManageable(User $actor, int $id): User
    {
        $user = $this->userQuery($actor)->findOrFail($id);

        if (! $actor->isSuperAdmin() && $user->hasRole('super_admin')) {
            abort(403);
        }

        return $user;
    }

    /** Roles the actor may assign. Non-super admins cannot create or grant super_admin. */
    private function assignableRoles(User $actor): array
    {
        return $actor->isSuperAdmin()
            ? self::ROLES
            : array_values(array_diff(self::ROLES, ['super_admin']));
    }

    /** Tenants the actor may assign a user to. Admins are limited to their own. */
    private function tenantsFor(User $actor)
    {
        return $actor->isSuperAdmin()
            ? Tenant::orderBy('name')->get(['id', 'name'])
            : Tenant::where('id', $actor->tenant_id)->get(['id', 'name']);
    }
}
