<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_pages_get_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_csp_uses_self_hosted_fonts_only(): void
    {
        // Inter/Fraunces are self-hosted (resources/css/fonts.css, bundled via Vite) so the
        // app runs fully offline. The CSP must NOT reference any external font CDN, and fonts
        // must be served from 'self'.
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('fonts.bunny.net', $csp);
        $this->assertStringNotContainsString('fonts.googleapis.com', $csp);
        $this->assertStringNotContainsString('fonts.gstatic.com', $csp);
        $this->assertStringContainsString("font-src 'self'", $csp);
    }

    public function test_build_assets_are_not_given_nosniff(): void
    {
        // Vite assets are served with a generic MIME by `php artisan serve`; applying
        // nosniff makes the browser refuse them, leaving the UI unstyled. The middleware
        // must leave /build/* responses untouched.
        $response = $this->get('/build/assets/does-not-exist.css');

        $this->assertNull($response->headers->get('X-Content-Type-Options'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }
}
