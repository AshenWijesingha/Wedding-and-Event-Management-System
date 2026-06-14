<?php

namespace App\Http\Middleware;

use App\Services\OnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects a fresh tenant owner/admin to the first-run setup wizard exactly
 * once. The wizard marks the tenant "seen" on render, so this fires a single
 * time; thereafter the dashboard checklist drives completion.
 */
class EnsureOnboarded
{
    public function __construct(private OnboardingService $onboarding)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && ! $request->routeIs('admin.onboarding.*')
            && $this->onboarding->shouldRedirect($user)) {
            return redirect()->route('admin.onboarding.show');
        }

        return $next($request);
    }
}
