<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;

/**
 * Computes first-run setup progress for a tenant. Step completion is derived
 * from real data (self-healing — no stale flags), while the one-time redirect
 * and checklist dismissal live in tenant.settings['onboarding'].
 */
class OnboardingService
{
    private const DEFAULT_PRIMARY = '#6366f1';
    private const REDIRECT_ROLES = ['super_admin', 'tenant_owner', 'admin'];

    /**
     * Per-step booleans + overall completion.
     *
     * @return array{branding:bool,venue:bool,package:bool,team:bool,completed:bool}
     */
    public function progress(Tenant $tenant): array
    {
        $steps = [
            'branding' => $this->brandingDone($tenant),
            'venue'    => Venue::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists(),
            'package'  => Package::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists(),
            'team'     => User::withoutGlobalScopes()->where('tenant_id', $tenant->id)
                ->whereIn('role', ['admin', 'manager', 'staff'])->exists(),
        ];

        return $steps + ['completed' => ! in_array(false, $steps, true)];
    }

    /**
     * Progress plus the seen/dismissed flags and the checklist visibility.
     */
    public function state(Tenant $tenant): array
    {
        $progress  = $this->progress($tenant);
        $meta      = $tenant->getSetting('onboarding', []) ?? [];
        $dismissed = (bool) ($meta['dismissed'] ?? false);

        return $progress + [
            'seen'           => (bool) ($meta['seen'] ?? false),
            'dismissed'      => $dismissed,
            'show_checklist' => ! $progress['completed'] && ! $dismissed,
        ];
    }

    /**
     * Whether this user should be force-redirected to the wizard (at most once).
     */
    public function shouldRedirect(User $user): bool
    {
        $tenant = Tenant::current();
        if (! $tenant || ! in_array($user->role, self::REDIRECT_ROLES, true)) {
            return false;
        }

        $state = $this->state($tenant);

        return ! $state['seen'] && ! $state['completed'];
    }

    public function markSeen(Tenant $tenant): void
    {
        $meta = $tenant->getSetting('onboarding', []) ?? [];
        $meta['seen'] = true;
        $tenant->setSetting('onboarding', $meta);
    }

    public function dismiss(Tenant $tenant): void
    {
        $meta = $tenant->getSetting('onboarding', []) ?? [];
        $meta['dismissed'] = true;
        $meta['seen'] = true;
        $tenant->setSetting('onboarding', $meta);
    }

    private function brandingDone(Tenant $tenant): bool
    {
        if (! empty($tenant->logo)) {
            return true;
        }
        if (! empty($tenant->primary_color) && $tenant->primary_color !== self::DEFAULT_PRIMARY) {
            return true;
        }

        $branding = $tenant->getSetting('branding', []) ?? [];

        return ! empty($branding['company_name']);
    }
}
