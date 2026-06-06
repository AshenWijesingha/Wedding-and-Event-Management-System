<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PluginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PluginController extends Controller
{
    public function __construct(private PluginService $pluginService) {}

    public function index(): Response
    {
        return Inertia::render('Plugins/Index', [
            'plugins'       => $this->pluginService->getAvailablePlugins(),
            'loadedPlugins' => array_keys($this->pluginService->getLoadedPlugins()),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $request->validate(['plugin' => 'required|string|max:100']);
        $success = $this->pluginService->enablePlugin($request->plugin);
        return back()->with($success ? 'success' : 'error', $success ? "Plugin enabled." : "Plugin not found.");
    }

    public function disable(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $request->validate(['plugin' => 'required|string|max:100']);
        $this->pluginService->disablePlugin($request->plugin);
        return back()->with('success', 'Plugin disabled.');
    }
}
