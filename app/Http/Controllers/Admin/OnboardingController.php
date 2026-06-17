<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * First-run setup wizard for the tenant team. Each step validates + creates a
 * record, then redirects back to the wizard so it advances with fresh,
 * data-derived progress.
 */
class OnboardingController extends Controller
{
    public function __construct(private OnboardingService $onboarding)
    {
    }

    public function show(): Response
    {
        $tenant = $this->tenant();
        $this->onboarding->markSeen($tenant);

        return Inertia::render('Onboarding/Wizard', [
            'progress' => $this->onboarding->progress($tenant),
            'tenant'   => [
                'name'          => $tenant->name,
                'email'         => $tenant->email,
                'phone'         => $tenant->phone,
                'primary_color' => $tenant->primary_color ?? '#6366f1',
            ],
        ]);
    }

    public function storeBranding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'company_name'  => 'nullable|string|max:255',
            'tagline'       => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
            'logo_url'      => 'nullable|url|max:500',
        ]);

        $tenant = $this->tenant();
        $tenant->update([
            'name'          => $data['name'],
            'primary_color' => $data['primary_color'] ?? $tenant->primary_color,
            'logo'          => $data['logo_url'] ?? $tenant->logo,
        ]);
        $tenant->setSetting('branding', [
            'company_name'  => $data['company_name'] ?? $data['name'],
            'tagline'       => $data['tagline'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
            'logo_url'      => $data['logo_url'] ?? null,
        ]);

        return redirect()->route('admin.onboarding.show')->with('success', 'Branding saved.');
    }

    public function storeVenue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'capacity_min' => 'required|integer|min:1',
            'capacity_max' => 'required|integer|min:1|gte:capacity_min',
            'base_price'   => 'required|numeric|min:0',
            'description'  => 'nullable|string',
        ]);

        Venue::create($data + [
            'tenant_id' => $this->tenant()->id,
            'slug'      => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'status'    => 'active',
        ]);

        return redirect()->route('admin.onboarding.show')->with('success', 'Venue added.');
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'base_price'  => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'min_guests'  => 'nullable|integer|min:1',
            'max_guests'  => 'nullable|integer|min:1',
        ]);

        Package::create([
            'tenant_id'   => $this->tenant()->id,
            'name'        => $data['name'],
            'base_price'  => $data['base_price'],
            'description' => $data['description'] ?? null,
            'min_guests'  => $data['min_guests'] ?? 1,
            'max_guests'  => $data['max_guests'] ?? 1000,
            'slug'        => Str::slug($data['name']).'-'.Str::lower(Str::random(5)),
            'status'      => 'active',
        ]);

        return redirect()->route('admin.onboarding.show')->with('success', 'Package added.');
    }

    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role'  => 'required|in:admin,manager,staff',
        ]);

        $password = Str::password(12);

        $user = User::create([
            'tenant_id' => $this->tenant()->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($password),
            'role'      => $data['role'],
            'is_active' => true,
        ]);
        $user->assignRole($data['role']);

        \App\Support\Notifier::mail($user->email, new \App\Mail\UserInvitedMail($user, $password));

        // The plaintext password is flashed once for the owner to hand over.
        // It is never stored or logged.
        return redirect()->route('admin.onboarding.show')->with([
            'invited_email'    => $user->email,
            'invited_password' => $password,
        ]);
    }

    public function finish(): RedirectResponse
    {
        $this->onboarding->dismiss($this->tenant());

        return redirect()->route('admin.dashboard')->with('success', 'Setup complete. You can revisit any step from the dashboard.');
    }

    /**
     * The acting user's own tenant. Bound to the authenticated user's tenant_id
     * (never a blind "first tenant" fallback, which could write to another
     * tenant if the current-tenant resolver ever returned null). Falls back to
     * the impersonated/current tenant only when the user has no tenant_id of
     * their own (e.g. a super admin impersonating).
     */
    private function tenant(): Tenant
    {
        $current = Tenant::current();
        $userTenantId = auth()->user()?->tenant_id;

        // Bind to the acting user's own tenant. Prefer the resolved current
        // tenant when it matches (same instance the onboarding state/redirect
        // reads), but never fall back to an arbitrary tenant — only the user's
        // own, or the impersonated current tenant when the user has none.
        if ($userTenantId && (! $current || $current->id !== $userTenantId)) {
            $current = Tenant::find($userTenantId);
        }

        abort_if($current === null, 403, 'No tenant configured.');

        return $current;
    }
}
