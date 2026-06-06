<?php

namespace Tests\Unit;

use App\Services\ThemeService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ThemeServiceTest extends TestCase
{
    private string $themesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->themesPath = resource_path('themes');
    }

    public function test_get_available_themes_returns_array(): void
    {
        $service = $this->makeService();
        $themes = $service->getAvailableThemes();

        $this->assertIsArray($themes);
    }

    public function test_default_active_theme_is_default(): void
    {
        $service = $this->makeService();

        $this->assertEquals('default', $service->getActiveTheme());
    }

    public function test_get_theme_config_returns_null_for_nonexistent(): void
    {
        $service = $this->makeService();
        $config = $service->getThemeConfig('nonexistent-theme-xyz');

        $this->assertNull($config);
    }

    public function test_get_theme_config_returns_array_for_default(): void
    {
        if (!File::isDirectory($this->themesPath . '/default')) {
            $this->markTestSkipped('Default theme not present on disk.');
        }

        $service = $this->makeService();
        $config = $service->getThemeConfig('default');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
    }

    public function test_set_active_theme_returns_false_for_nonexistent(): void
    {
        $service = $this->makeService();
        $result = $service->setActiveTheme('does-not-exist-xyz');

        $this->assertFalse($result);
    }

    public function test_set_active_theme_returns_true_for_existing(): void
    {
        if (!File::isDirectory($this->themesPath . '/default')) {
            $this->markTestSkipped('Default theme not present on disk.');
        }

        $service = $this->makeService();
        $result = $service->setActiveTheme('default');

        $this->assertTrue($result);
        $this->assertEquals('default', $service->getActiveTheme());
    }

    public function test_has_view_returns_false_when_view_missing(): void
    {
        $service = $this->makeService();

        $this->assertFalse($service->hasView('nonexistent-view-xyz'));
    }

    private function makeService(): ThemeService
    {
        // Bind a null currentTenant so ThemeService doesn't throw
        app()->bind('currentTenant', fn () => null);

        return new ThemeService();
    }
}
