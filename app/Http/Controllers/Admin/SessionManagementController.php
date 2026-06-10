<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\UserAgentParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Self-service "active sessions / devices" management for the logged-in user.
 * Works only with the database session driver (the sessions are enumerable there);
 * with any other driver the page renders an informational notice.
 */
class SessionManagementController extends Controller
{
    private function databaseDriver(): bool
    {
        return config('session.driver') === 'database';
    }

    private function basePath(Request $request): string
    {
        return $request->routeIs('portal.*') ? '/portal/sessions' : '/admin/profile/sessions';
    }

    public function index(Request $request): Response
    {
        $supported = $this->databaseDriver();
        $currentId = $request->session()->getId();

        $sessions = $supported
            ? DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('last_activity')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'ip' => $s->ip_address,
                    'device' => UserAgentParser::describe($s->user_agent),
                    'last_active' => Carbon::createFromTimestamp($s->last_activity)->diffForHumans(),
                    'is_current' => $s->id === $currentId,
                ])
                ->values()
            : collect();

        return Inertia::render('Settings/Sessions', [
            'supported' => $supported,
            'sessions' => $sessions,
            'basePath' => $this->basePath($request),
        ]);
    }

    /**
     * Revoke a single other session. The current session can only be ended via logout.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        abort_unless($this->databaseDriver(), 404);

        if ($id === $request->session()->getId()) {
            return back()->with('error', 'Use “Log out” to end your current session.');
        }

        $deleted = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        abort_if($deleted === 0, 404);

        activity('session')
            ->causedBy($request->user())
            ->log('Revoked a session');

        return back()->with('success', 'That session has been signed out.');
    }

    /**
     * Revoke every session except the current one ("log out all other devices").
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        abort_unless($this->databaseDriver(), 404);

        // Deleting the rows immediately invalidates those sessions under the database
        // driver — the next request on them finds no session and is treated as a guest.
        $count = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        activity('session')
            ->causedBy($request->user())
            ->log("Logged out {$count} other session(s)");

        return back()->with('success', "Signed out of {$count} other session(s).");
    }
}
