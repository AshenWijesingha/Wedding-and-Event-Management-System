<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Records per-user completion of an in-app guided tour. Writes only the
 * caller's own preferences; idempotent.
 */
class TourController extends Controller
{
    /** Allow-list mirrors the frontend tour registry keys. */
    public const KEYS = [
        'dashboard', 'bookings', 'booking-show', 'booking-create', 'inquiries',
        'quotations', 'quotation-show', 'clients', 'venues', 'packages',
        'vendors', 'tasks', 'payments', 'reports', 'settings',
    ];

    public function complete(Request $request): Response
    {
        $data = $request->validate([
            'key' => ['required', 'string', Rule::in(self::KEYS)],
        ]);

        $user  = $request->user();
        $prefs = $user->preferences ?? [];
        $done  = $prefs['completed_tours'] ?? [];

        if (! in_array($data['key'], $done, true)) {
            $done[] = $data['key'];
            $prefs['completed_tours'] = array_values($done);
            $user->preferences = $prefs;
            $user->save();
        }

        return response()->noContent();
    }
}
