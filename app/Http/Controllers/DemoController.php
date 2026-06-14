<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\DemoTenantProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Public entry point for the demo sandbox. Provisions an isolated throwaway
 * tenant, logs the visitor in as its owner, and drops them on the dashboard.
 */
class DemoController extends Controller
{
    public function start(DemoTenantProvisioner $provisioner): RedirectResponse
    {
        abort_unless(config('eventpro.demo.enabled'), 404);

        // Global live-tenant cap protects the database from pile-up.
        $maxLive = (int) config('eventpro.demo.max_live', 50);
        $live = Tenant::where('is_demo', true)->where('demo_expires_at', '>', now())->count();
        if ($live >= $maxLive) {
            return redirect('/')->with('error', 'The demo is at capacity right now — please try again in a few minutes.');
        }

        ['user' => $user] = $provisioner->provision();
        Auth::login($user);

        return redirect('/admin');
    }
}
