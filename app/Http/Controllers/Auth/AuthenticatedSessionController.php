<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        // Demo prefill buttons (incl. the super-admin one) are only exposed outside
        // production so real deployments never surface seeded credentials.
        $canUseDemo = ! app()->environment('production');

        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
            'canUseDemo' => $canUseDemo,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        // Stamp session lifetimes for EnforceSessionTimeout (idle + absolute).
        $now = time();
        $request->session()->put('auth.login_at', $now);
        $request->session()->put('auth.last_activity_at', $now);

        return redirect()->intended($this->homeFor($request->user()));
    }

    /**
     * Landing route for a freshly authenticated user, by role. Clients live in the
     * portal; everyone else (staff and above) lands in the admin area, whose own
     * controller further branches super admins to the platform dashboard.
     */
    private function homeFor(\App\Models\User $user): string
    {
        if ($user->hasRole('client')) {
            return route('portal.dashboard');
        }

        return route('admin.dashboard');
    }

    public function destroy(Request $request): HttpResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Logout is fired from the Inertia SPA, but '/' is a plain Blade page.
        // Inertia::location() makes the client perform a real full-page visit
        // (409 + X-Inertia-Location) instead of choking on the HTML response and
        // rendering it inside its error modal. Plain requests get a normal 302.
        return Inertia::location('/');
    }
}
