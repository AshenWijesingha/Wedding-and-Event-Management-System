<?php

namespace App\Multitenancy;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $host = $request->getHost();

        // 1. Match exact domain (e.g. venue.com or custom domain)
        $tenant = Tenant::where('domain', $host)->where('status', '!=', 'suspended')->first();
        if ($tenant) {
            return $tenant;
        }

        // 2. Match by subdomain slug (e.g. grand-palace.eventpro.test → slug = grand-palace)
        $appDomain = config('app.url');
        $baseDomain = parse_url($appDomain, PHP_URL_HOST) ?? 'localhost';

        if (str_ends_with($host, '.' . $baseDomain)) {
            $slug = str_replace('.' . $baseDomain, '', $host);

            return Tenant::where('slug', $slug)->where('status', '!=', 'suspended')->first();
        }

        // 3. Single-tenant mode: return first active tenant (no subdomain needed)
        if (config('eventpro.tenancy_mode') === 'single') {
            return Tenant::where('status', '!=', 'suspended')->first();
        }

        return null;
    }
}
