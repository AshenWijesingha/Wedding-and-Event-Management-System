<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Settings\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Global platform configuration. Super-admin only (enforced by the route group).
 */
class PlatformSettingsController extends Controller
{
    public function edit(PlatformSettings $settings): Response
    {
        return Inertia::render('Platform/Settings', [
            'settings' => [
                'platform_name' => $settings->platform_name,
                'default_plan_id' => $settings->default_plan_id,
                'signups_enabled' => $settings->signups_enabled,
                'support_email' => $settings->support_email,
            ],
            'plans' => Plan::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, PlatformSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => 'required|string|max:255',
            'default_plan_id' => 'nullable|exists:plans,id',
            'signups_enabled' => 'required|boolean',
            'support_email' => 'nullable|email|max:255',
        ]);

        $settings->platform_name = $validated['platform_name'];
        $settings->default_plan_id = $validated['default_plan_id'] ?? null;
        $settings->signups_enabled = $validated['signups_enabled'];
        $settings->support_email = $validated['support_email'] ?? null;
        $settings->save();

        return back()->with('success', 'Platform settings saved.');
    }
}
