<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    /**
     * Make the authenticated user's tenant the current tenant for the request.
     *
     * The domain-based tenant finder only runs when Spatie's NeedsTenant middleware
     * is applied. For the authenticated admin/portal areas the natural tenant is the
     * one the logged-in user belongs to, so we resolve it here. This ensures the
     * BelongsToTenant global scope filters data and auto-fills tenant_id on create.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Tenant::checkCurrent()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            $tenant?->makeCurrent();
        }

        return $next($request);
    }
}
