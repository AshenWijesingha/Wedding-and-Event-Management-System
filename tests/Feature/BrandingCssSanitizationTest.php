<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\BrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingCssSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_malicious_color_settings_cannot_inject_markup(): void
    {
        $tenant = Tenant::factory()->create([
            'primary_color' => '</style><script>alert(1)</script>',
        ]);
        $tenant->setSetting('branding.secondary_color', '</style><script>document.location="//evil"</script>');
        $tenant->setSetting('branding.accent_color', 'red;}body{display:none');

        $css = (new BrandingService($tenant))->getCssVariables();

        $this->assertStringNotContainsString('<script', $css);
        $this->assertStringNotContainsString('</style', $css);
        $this->assertStringNotContainsString('display:none', $css);
        // Falls back to a safe default for the rejected primary color.
        $this->assertStringContainsString('--color-primary: #3B82F6;', $css);
    }

    public function test_valid_colors_pass_through(): void
    {
        $tenant = Tenant::factory()->create(['primary_color' => '#abcdef']);
        $tenant->setSetting('branding.secondary_color', 'rgb(10, 20, 30)');

        $css = (new BrandingService($tenant))->getCssVariables();

        $this->assertStringContainsString('--color-primary: #abcdef;', $css);
        $this->assertStringContainsString('--color-secondary: rgb(10, 20, 30);', $css);
    }
}
