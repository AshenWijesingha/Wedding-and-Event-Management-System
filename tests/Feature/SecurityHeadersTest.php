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

    public function test_csp_allows_bunny_fonts(): void
    {
        // The layout loads Inter/Fraunces from fonts.bunny.net; the CSP must permit it
        // or the browser blocks the fonts.
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://fonts.bunny.net', $csp);
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
