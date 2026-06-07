<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /** Roles a super admin may assign. */
    private const ROLES = ['super_admin', 'admin', 'manager', 'staff', 'client'];

    public function index(Request $request): Response
    {
        $users = User::withoutTenantScope()
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

        $all = User::withoutTenantScope();

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles'   => self::ROLES,
            'stats'   => [
                'total'    => (clone $all)->count(),
                'active'   => (clone $all)->where('is_active', true)->count(),
                'inactive' => (clone $all)->where('is_active', false)->count(),
                'byRole'   => (clone $all)->selectRaw('role, COUNT(*) as count')->groupBy('role')->pluck('count', 'role'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'roles'   => self::ROLES,
            'tenants' => Tenant::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role'      => ['required', Rule::in(self::ROLES)],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'phone'     => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'password'  => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = new User();
        $user->forceFill([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'tenant_id' => $validated['tenant_id'],
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'password'  => $validated['password'],
        ])->save();

        $user->syncRoles([$validated['role']]);

        return redirect('/admin/users')->with('success', 'User created.');
    }

    public function edit(int $id): Response
    {
        $user = User::withoutTenantScope()->findOrFail($id);

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
            'roles'   => self::ROLES,
            'tenants' => Tenant::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::withoutTenantScope()->findOrFail($id);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'      => ['required', Rule::in(self::ROLES)],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'phone'     => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        // A super admin must not lock themselves out of their own role/account.
        if ($user->id === $request->user()->id && $validated['role'] !== 'super_admin') {
            return back()->with('error', 'You cannot change your own super admin role.');
        }

        $user->forceFill([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'tenant_id' => $validated['tenant_id'],
            'phone'     => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        $user->syncRoles([$validated['role']]);

        return redirect('/admin/users')->with('success', 'User updated.');
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $user = User::withoutTenantScope()->findOrFail($id);

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => $request->password]);

        return back()->with('success', "Password reset for {$user->name}.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = User::withoutTenantScope()->findOrFail($id);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect('/admin/users')->with('success', 'User deleted.');
    }
}
