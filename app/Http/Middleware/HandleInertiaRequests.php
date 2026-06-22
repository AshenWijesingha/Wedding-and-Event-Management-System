<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\OnboardingService;
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
                ? ['tenant' => optional(Tenant::current())->name ?? 'tenant']
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
            // Demo-sandbox banner state when the current tenant is a demo.
            'demo' => fn () => $this->demoState(),
            // In-app notifications for the topbar bell (recent + unread count).
            'notifications' => fn () => $this->notificationsState($request),
        ];
    }

    /**
     * Recent in-app notifications + unread count for the authenticated user.
     */
    private function notificationsState(Request $request): ?array
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return [
            'items' => $user->notifications()->latest()->limit(10)->get()->map(fn ($n) => [
                'id' => $n->id,
                'event' => $n->data['event'] ?? null,
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? null,
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->diffForHumans(),
            ])->all(),
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }

    /**
     * Banner state for an ephemeral demo tenant, or null.
     */
    private function demoState(): ?array
    {
        $tenant = Tenant::current();
        if (! $tenant || ! $tenant->is_demo) {
            return null;
        }

        return [
            'is_demo' => true,
            'expires_at' => optional($tenant->demo_expires_at)->toIso8601String(),
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

        $tenant = Tenant::current();
        if (! $tenant) {
            return null;
        }

        return app(OnboardingService::class)->state($tenant);
    }
}
