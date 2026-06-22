<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ThemeController extends Controller
{
    public function __construct(private ThemeService $themeService) {}

    public function index(): Response
    {
        return Inertia::render('Themes/Index', [
            'themes' => $this->themeService->getAvailableThemes(),
            'activeTheme' => $this->themeService->getActiveTheme(),
        ]);
    }

    public function activate(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $request->validate(['theme' => 'required|string|max:100']);

        $success = $this->themeService->setActiveTheme($request->theme);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? "Theme \"{$request->theme}\" activated." : 'Theme not found.'
        );
    }
}
