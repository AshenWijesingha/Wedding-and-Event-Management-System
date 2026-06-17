<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark one notification read (when `id` is given) or all of the user's
     * unread notifications. Mirrors the portal notifications.read endpoint.
     */
    public function read(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($request->filled('id')) {
            $user->notifications()->where('id', $request->id)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return back();
    }
}
