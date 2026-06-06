<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app('currentTenant')) {
            abort(404, 'Tenant not found.');
        }

        $tenant = app('currentTenant');

        if ($tenant->status === 'suspended') {
            abort(403, 'This account has been suspended.');
        }

        return $next($request);
    }
}
