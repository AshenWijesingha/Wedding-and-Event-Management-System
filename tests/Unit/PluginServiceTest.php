<?php

namespace Tests\Unit;

use App\Services\PluginService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginServiceTest extends TestCase
{
    public function test_get_available_plugins_returns_array(): void
    {
        $service = $this->makeService();
        $plugins = $service->getAvailablePlugins();

        $this->assertIsArray($plugins);
    }

    public function test_is_plugin_loaded_false_by_default(): void
    {
        $service = $this->makeService();

        $this->assertFalse($service->isPluginLoaded('sms-gateway'));
    }

    public function test_load_plugin_returns_false_for_nonexistent(): void
    {
        $service = $this->makeService();
        $result = $service->loadPlugin('plugin-does-not-exist-xyz');

        $this->assertFalse($result);
    }

    public function test_load_plugin_returns_true_for_existing(): void
    {
        $pluginPath = base_path('plugins/sms-gateway');
        if (! File::isDirectory($pluginPath)) {
            $this->markTestSkipped('sms-gateway plugin not present on disk.');
        }

        $service = $this->makeService();
        $result = $service->loadPlugin('sms-gateway');

        $this->assertTrue($result);
        $this->assertTrue($service->isPluginLoaded('sms-gateway'));
    }

    public function test_get_loaded_plugins_returns_empty_initially(): void
    {
        $service = $this->makeService();

        $this->assertEmpty($service->getLoadedPlugins());
    }

    public function test_execute_hook_returns_array(): void
    {
        $service = $this->makeService();
        $results = $service->executeHook('notification.sms', ['message' => 'test']);

        $this->assertIsArray($results);
    }

    public function test_available_plugins_contain_expected_keys(): void
    {
        $pluginPath = base_path('plugins/sms-gateway');
        if (! File::isDirectory($pluginPath)) {
            $this->markTestSkipped('sms-gateway plugin not present on disk.');
        }

        $service = $this->makeService();
        $plugins = $service->getAvailablePlugins();

        $this->assertNotEmpty($plugins);
        $first = $plugins[0];
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('slug', $first);
        $this->assertArrayHasKey('version', $first);
    }

    private function makeService(): PluginService
    {
        app()->bind('currentTenant', fn () => null);

        return new PluginService;
    }
}
