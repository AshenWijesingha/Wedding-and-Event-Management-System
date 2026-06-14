<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                // Flattened permission + role names so the frontend can gate nav items
                // and action buttons. Server-side middleware/policies remain the source
                // of truth; this is purely for showing/hiding UI.
                'permissions' => fn () => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->values()
                    : [],
                'roles' => fn () => $request->user()
                    ? $request->user()->getRoleNames()->values()
                    : [],
            ],
            'impersonating' => fn () => $request->session()->has('impersonator_id')
                ? ['tenant' => optional(\App\Models\Tenant::current())->name ?? 'tenant']
                : null,
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                // One-time credentials shown after inviting a team member.
                'invited_email' => fn () => $request->session()->get('invited_email'),
                'invited_password' => fn () => $request->session()->get('invited_password'),
            ],
            // First-run setup progress, for admin-area roles with a current tenant.
            // Powers the dashboard "Getting started" checklist.
            'onboarding' => fn () => $this->onboardingState($request),
            // Guided-tour keys this user has already completed.
            'completedTours' => fn () => $request->user()?->preferences['completed_tours'] ?? [],
        ];
    }

    /**
     * Onboarding state for the current tenant, or null when not applicable.
     */
    private function onboardingState(Request $request): ?array
    {
        $user = $request->user();
        if (! $user || $user->role === 'client') {
            return null;
        }

        $tenant = \App\Models\Tenant::current();
        if (! $tenant) {
            return null;
        }

        return app(\App\Services\OnboardingService::class)->state($tenant);
    }
}
