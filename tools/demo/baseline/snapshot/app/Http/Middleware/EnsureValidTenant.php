<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Tenant::current()) {
            abort(404, 'Tenant not found.');
        }

        $tenant = Tenant::current();

        if ($tenant->status === 'suspended') {
            abort(403, 'This account has been suspended.');
        }

        return $next($request);
    }
}
