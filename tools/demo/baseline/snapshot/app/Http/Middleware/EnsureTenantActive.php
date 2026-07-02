<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access for users whose tenant has been suspended. Platform super admins
 * (no tenant) and active impersonation sessions are allowed through so support
 * staff can still investigate a suspended tenant.
 */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('super_admin') || $request->session()->has('impersonator_id')) {
            return $next($request);
        }

        $tenant = Tenant::current();

        if ($tenant && $tenant->status === 'suspended') {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'This account has been suspended. Please contact support.',
            ]);
        }

        return $next($request);
    }
}
