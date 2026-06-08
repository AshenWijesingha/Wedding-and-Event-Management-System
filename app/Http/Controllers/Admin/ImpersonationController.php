<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a super admin impersonate a tenant by logging in as one of the tenant's
 * owner/admin users, then return to their own account. The original user id is
 * stashed in the session so the action is reversible.
 */
class ImpersonationController extends Controller
{
    public function start(Request $request, Tenant $tenant): RedirectResponse
    {
        // Guard: cannot start a second impersonation on top of an active one.
        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'Already impersonating. Exit first.');
        }

        $usersOfTenant = fn () => User::withoutTenantScope()->where('tenant_id', $tenant->id);

        $target = $usersOfTenant()->where('role', 'tenant_owner')->first()
            ?? $usersOfTenant()->where('role', 'admin')->first()
            ?? $usersOfTenant()->first();

        if (! $target) {
            return back()->with('error', 'This tenant has no users to impersonate.');
        }

        $request->session()->put('impersonator_id', $request->user()->id);

        Auth::login($target);

        return redirect('/admin')->with('success', "You are now impersonating {$tenant->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $originalId = $request->session()->pull('impersonator_id');

        if (! $originalId) {
            return redirect('/admin');
        }

        $original = User::withoutTenantScope()->find($originalId);

        if ($original) {
            Auth::login($original);
        }

        return redirect('/admin/tenants')->with('success', 'Impersonation ended.');
    }
}
