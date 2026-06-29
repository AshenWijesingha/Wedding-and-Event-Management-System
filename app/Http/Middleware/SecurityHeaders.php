<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Vite-built static assets (/build/*) are served by `php artisan serve`
        // with a generic "text/html" MIME type. Combined with the nosniff header
        // below, the browser refuses to apply them as CSS / ES modules, leaving
        // the whole UI unstyled. Real web servers (nginx/apache) serve these with
        // the correct MIME and bypass PHP entirely, so it is safe to leave asset
        // responses untouched here.
        if ($request->is('build/*')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (! app()->isLocal()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        app()->instance('csp_nonce', $nonce);

        // In local development the Vite dev server (`npm run dev`) serves the JS/CSS
        // and HMR websocket from a separate origin. Without allowing it in the CSP
        // the browser blocks those assets and the whole UI renders unstyled.
        $vite = '';
        $viteWs = '';
        if (app()->isLocal()) {
            $hosts = ['localhost:5173', '127.0.0.1:5173', '[::1]:5173'];
            $vite = ' ' . implode(' ', array_map(fn ($h) => "http://{$h}", $hosts));
            $viteWs = ' ' . implode(' ', array_map(fn ($h) => "ws://{$h}", $hosts));
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$vite}",
            "style-src 'self' 'unsafe-inline'{$vite}",
            "font-src 'self' data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'{$vite}{$viteWs}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        // Remove fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
