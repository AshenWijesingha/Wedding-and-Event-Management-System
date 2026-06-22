<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-level tenant administration. Restricted to super admins via the
 * route group. Tenants are landlord records (not BelongsToTenant), so queries
 * here are intentionally unscoped.
 */
class TenantController extends Controller
{
    private const STATUSES = ['active', 'trial', 'suspended'];

    public function index(Request $request): Response
    {
        $tenants = Tenant::query()
            ->with('plan:id,name')
            ->withCount(['users', 'bookings'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('slug', 'like', "%{$s}%")->orWhere('domain', 'like', "%{$s}%"))
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Tenant $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'domain' => $t->domain,
                'status' => $t->status,
                'plan' => $t->plan?->name,
                'users_count' => $t->users_count,
                'bookings_count' => $t->bookings_count,
                'trial_ends_at' => $t->trial_ends_at?->toDateString(),
                'created_at' => $t->created_at?->toDateString(),
            ]);

        return Inertia::render('Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
            'statuses' => self::STATUSES,
            'stats' => [
                'total' => Tenant::count(),
                'active' => Tenant::where('status', 'active')->count(),
                'trial' => Tenant::where('status', 'trial')->count(),
                'suspended' => Tenant::where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenants/Create', [
            'plans' => Plan::orderBy('sort_order')->get(['id', 'name']),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'primary_color' => 'nullable|string|max:20',
            'trial_ends_at' => 'nullable|date',
            // Tenant owner provisioned alongside the tenant.
            'owner_name' => 'required|string|max:255',
            'owner_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'domain' => $validated['domain'] ?? null,
                'plan_id' => $validated['plan_id'] ?? null,
                'status' => $validated['status'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'primary_color' => $validated['primary_color'] ?? '#6366f1',
                'trial_ends_at' => $validated['trial_ends_at'] ?? null,
                'settings' => [],
            ]);

            $owner = new User;
            $owner->forceFill([
                'tenant_id' => $tenant->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'role' => 'tenant_owner',
                'password' => $validated['owner_password'],
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();

            $owner->syncRoles(['tenant_owner']);
        });

        return redirect('/admin/tenants')->with('success', 'Tenant created with its owner account.');
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Tenants/Edit', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'plan_id' => $tenant->plan_id,
                'status' => $tenant->status,
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'primary_color' => $tenant->primary_color,
                'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d'),
            ],
            'plans' => Plan::orderBy('sort_order')->get(['id', 'name']),
            'statuses' => self::STATUSES,
            'owners' => User::withoutTenantScope()
                ->where('tenant_id', $tenant->id)
                ->where('role', 'tenant_owner')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant->id)],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'primary_color' => 'nullable|string|max:20',
            'trial_ends_at' => 'nullable|date',
        ]);

        $validated['primary_color'] = $validated['primary_color'] ?? $tenant->primary_color ?? '#6366f1';

        $tenant->update($validated);

        return redirect('/admin/tenants')->with('success', 'Tenant updated.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'suspended']);

        return back()->with('success', "{$tenant->name} has been suspended.");
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'active']);

        return back()->with('success', "{$tenant->name} has been reactivated.");
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        // Guard against destroying a tenant that still has booking history; suspend instead.
        if (Booking::withoutTenantScope()->where('tenant_id', $tenant->id)->exists()) {
            return back()->with('error', 'Cannot delete a tenant with bookings. Suspend it instead.');
        }

        DB::transaction(function () use ($tenant) {
            User::withoutTenantScope()->where('tenant_id', $tenant->id)->delete();
            $tenant->delete();
        });

        return redirect('/admin/tenants')->with('success', 'Tenant deleted.');
    }
}
