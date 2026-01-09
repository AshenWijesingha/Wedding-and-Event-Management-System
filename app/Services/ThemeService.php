<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class ThemeService
{
    private string $activeTheme = 'default';

    public function __construct()
    {
        $this->loadActiveTheme();
    }

    /**
     * Load and activate the current tenant's theme.
     */
    private function loadActiveTheme(): void
    {
        $tenant = app('currentTenant');
        if ($tenant) {
            $this->activeTheme = $tenant->getSetting('theme.active', 'default');
        }
        $this->registerThemeViews();
    }

    /**
     * Get all available themes.
     */
    public function getAvailableThemes(): array
    {
        $themesPath = resource_path('themes');
        $themes = [];

        if (!File::isDirectory($themesPath)) {
            return $themes;
        }

        foreach (File::directories($themesPath) as $themePath) {
            $configPath = "{$themePath}/theme.json";
            if (File::exists($configPath)) {
                $themes[basename($themePath)] = json_decode(File::get($configPath), true);
            }
        }

        return $themes;
    }

    /**
     * Get active theme name.
     */
    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }

    /**
     * Get theme configuration.
     */
    public function getThemeConfig(?string $theme = null): ?array
    {
        $theme = $theme ?? $this->activeTheme;
        $configPath = resource_path("themes/{$theme}/theme.json");
        
        if (File::exists($configPath)) {
            return json_decode(File::get($configPath), true);
        }

        return null;
    }

    /**
     * Set active theme.
     */
    public function setActiveTheme(string $theme): bool
    {
        $themePath = resource_path("themes/{$theme}");
        
        if (!File::isDirectory($themePath)) {
            return false;
        }

        $this->activeTheme = $theme;
        $this->registerThemeViews();

        // Save to tenant settings if tenant exists
        $tenant = app('currentTenant');
        if ($tenant) {
            $tenant->setSetting('theme.active', $theme);
        }

        return true;
    }

    /**
     * Register theme view paths.
     */
    private function registerThemeViews(): void
    {
        $themePath = resource_path("themes/{$this->activeTheme}/views");
        if (File::isDirectory($themePath)) {
            View::prependLocation($themePath);
        }
    }

    /**
     * Get asset URL for current theme.
     */
    public function asset(string $path): string
    {
        return asset("themes/{$this->activeTheme}/assets/{$path}");
    }

    /**
     * Check if a theme view exists.
     */
    public function hasView(string $view): bool
    {
        $themePath = resource_path("themes/{$this->activeTheme}/views/{$view}.blade.php");
        return File::exists($themePath);
    }
}
