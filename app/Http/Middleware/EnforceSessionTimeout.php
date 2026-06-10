<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces two session lifetimes for authenticated users:
 *   - idle: expire after a period of inactivity (config session.idle_timeout)
 *   - absolute: expire once the session exceeds a hard maximum age regardless of
 *     activity (config session.absolute_timeout)
 *
 * On expiry the user is logged out, the session is invalidated, and they are
 * redirected to the login screen with an explanatory status message rather than
 * hitting a stale-CSRF 419 or a silent failure.
 */
class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $now = time();
        $idle = (int) config('session.idle_timeout', 3600);
        $absolute = (int) config('session.absolute_timeout', 43200);
        $session = $request->session();

        // Stamp the login time once (covers sessions created before this middleware existed).
        $loginAt = $session->get('auth.login_at');
        if ($loginAt === null) {
            $loginAt = $now;
            $session->put('auth.login_at', $loginAt);
        }

        $lastActivity = $session->get('auth.last_activity_at', $now);

        $absoluteExpired = ($now - $loginAt) > $absolute;
        $idleExpired = ($now - $lastActivity) > $idle;

        if ($absoluteExpired || $idleExpired) {
            Auth::guard('web')->logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()->route('login')->with(
                'status',
                'Your session expired for security. Please sign in again.'
            );
        }

        $session->put('auth.last_activity_at', $now);

        return $next($request);
    }
}
