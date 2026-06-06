<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PluginService
{
    private array $loadedPlugins = [];
    private array $hooks = [];

    /**
     * Load all enabled plugins for current tenant.
     */
    public function loadPlugins(): void
    {
        $tenant = app('currentTenant');
        $enabledPlugins = $tenant?->getSetting('plugins.enabled', []) ?? [];

        foreach ($enabledPlugins as $pluginSlug) {
            $this->loadPlugin($pluginSlug);
        }
    }

    /**
     * Load a specific plugin.
     */
    public function loadPlugin(string $slug): bool
    {
        if (!preg_match('/^[a-z0-9\-_]+$/', $slug)) {
            logger()->warning("PluginService: rejected invalid plugin slug '{$slug}'.");
            return false;
        }

        $configPath = base_path("plugins/{$slug}/plugin.json");
        if (!File::exists($configPath)) {
            return false;
        }

        $config = json_decode(File::get($configPath), true);

        // Register providers
        foreach ($config['providers'] ?? [] as $provider) {
            if (class_exists($provider)) {
                app()->register($provider);
            }
        }

        // Register hooks
        foreach ($config['hooks'] ?? [] as $hook => $handler) {
            $this->hooks[$hook][] = ['plugin' => $slug, 'handler' => $handler];
        }

        $this->loadedPlugins[$slug] = $config;
        return true;
    }

    /**
     * Execute all handlers for a hook.
     */
    public function executeHook(string $hook, array $data = []): array
    {
        $results = [];
        
        foreach ($this->hooks[$hook] ?? [] as $handler) {
            $plugin = $this->loadedPlugins[$handler['plugin']] ?? null;
            if (!$plugin) {
                continue;
            }

            // Get the service class for the plugin
            $serviceClass = "Plugins\\{$this->studly($handler['plugin'])}\\Services\\{$this->studly($handler['plugin'])}Service";
            
            if (class_exists($serviceClass)) {
                $service = app($serviceClass);
                if (method_exists($service, $handler['handler'])) {
                    $results[] = $service->{$handler['handler']}($data);
                }
            }
        }

        return $results;
    }

    /**
     * Get all available plugins.
     */
    public function getAvailablePlugins(): array
    {
        $plugins = [];
        $pluginsPath = base_path('plugins');

        if (!File::isDirectory($pluginsPath)) {
            return $plugins;
        }

        foreach (File::directories($pluginsPath) as $pluginPath) {
            $configPath = "{$pluginPath}/plugin.json";
            if (File::exists($configPath)) {
                $config = json_decode(File::get($configPath), true);
                $config['path'] = $pluginPath;
                $config['is_loaded'] = isset($this->loadedPlugins[basename($pluginPath)]);
                $plugins[] = $config;
            }
        }

        return $plugins;
    }

    /**
     * Get loaded plugins.
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * Check if a plugin is loaded.
     */
    public function isPluginLoaded(string $slug): bool
    {
        return isset($this->loadedPlugins[$slug]);
    }

    /**
     * Enable a plugin for current tenant.
     */
    public function enablePlugin(string $slug): bool
    {
        $tenant = app('currentTenant');
        if (!$tenant) {
            return false;
        }

        $enabled = $tenant->getSetting('plugins.enabled', []);
        if (!in_array($slug, $enabled)) {
            $enabled[] = $slug;
            $tenant->setSetting('plugins.enabled', $enabled);
        }

        return $this->loadPlugin($slug);
    }

    /**
     * Disable a plugin for current tenant.
     */
    public function disablePlugin(string $slug): bool
    {
        $tenant = app('currentTenant');
        if (!$tenant) {
            return false;
        }

        $enabled = $tenant->getSetting('plugins.enabled', []);
        $enabled = array_diff($enabled, [$slug]);
        $tenant->setSetting('plugins.enabled', array_values($enabled));

        unset($this->loadedPlugins[$slug]);
        return true;
    }

    /**
     * Convert string to StudlyCase.
     */
    private function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}
