<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
    private ?Tenant $tenant;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? Tenant::current();
    }

    /**
     * Get all branding information.
     */
    public function getBranding(): array
    {
        if (! $this->tenant) {
            return $this->getDefaultBranding();
        }

        return [
            'business_name' => $this->tenant->name,
            'tagline' => $this->tenant->getSetting('general.tagline'),
            'logo' => $this->getLogoUrl(),
            'logo_dark' => $this->getLogoUrl('dark'),
            'logo_pdf' => $this->getLogoDataUri(),
            'favicon' => $this->getFaviconUrl(),
            'colors' => [
                'primary' => $this->tenant->primary_color ?? '#3B82F6',
                'secondary' => $this->tenant->getSetting('branding.secondary_color', '#1E40AF'),
                'accent' => $this->tenant->getSetting('branding.accent_color', '#10B981'),
            ],
            'contact' => [
                'email' => $this->tenant->email,
                'phone' => $this->tenant->phone,
                'address' => $this->tenant->getSetting('contact.address'),
                'city' => $this->tenant->getSetting('contact.city'),
                'country' => $this->tenant->getSetting('contact.country'),
            ],
            'social' => [
                'facebook' => $this->tenant->getSetting('social.facebook'),
                'instagram' => $this->tenant->getSetting('social.instagram'),
                'twitter' => $this->tenant->getSetting('social.twitter'),
                'linkedin' => $this->tenant->getSetting('social.linkedin'),
                'youtube' => $this->tenant->getSetting('social.youtube'),
            ],
        ];
    }

    /**
     * Get CSS variables for theming.
     */
    public function getCssVariables(): string
    {
        $branding = $this->getBranding();

        // These values are interpolated raw into an inline <style> block, so they
        // MUST be sanitized to a CSS-color shape. Tenant colors are attacker-
        // controlled (tenant admins set them via settings), and an unsanitized
        // value like "</style><script>…" would be stored XSS on every page.
        $primary = $this->safeColor($branding['colors']['primary'] ?? null, '#3B82F6');
        $secondary = $this->safeColor($branding['colors']['secondary'] ?? null, '#1E40AF');
        $accent = $this->safeColor($branding['colors']['accent'] ?? null, '#10B981');

        return <<<CSS
:root {
    --color-primary: {$primary};
    --color-primary-dark: {$this->darkenColor($primary, 20)};
    --color-primary-light: {$this->lightenColor($primary, 20)};
    --color-secondary: {$secondary};
    --color-secondary-dark: {$this->darkenColor($secondary, 20)};
    --color-secondary-light: {$this->lightenColor($secondary, 20)};
    --color-accent: {$accent};
}
CSS;
    }

    /**
     * Get logo URL.
     */
    private function getLogoUrl(?string $variant = null): string
    {
        $settingKey = $variant ? "branding.logo_{$variant}" : 'branding.logo';
        $logo = $this->tenant?->getSetting($settingKey) ?? $this->tenant?->logo;

        if ($logo && Storage::disk('public')->exists($logo)) {
            return Storage::disk('public')->url($logo);
        }

        return asset('images/logo.svg');
    }

    /**
     * Get favicon URL.
     */
    private function getFaviconUrl(): string
    {
        $favicon = $this->tenant?->getSetting('branding.favicon') ?? $this->tenant?->favicon;

        if ($favicon && Storage::disk('public')->exists($favicon)) {
            return Storage::disk('public')->url($favicon);
        }

        return asset('images/favicon.svg');
    }

    /**
     * Resolve the tenant's uploaded logo to a base64 data URI for embedding in
     * PDFs. dompdf cannot fetch remote http(s) URLs (and we keep remote fetching
     * off for safety/offline), so the bytes are inlined directly.
     *
     * Returns null when the tenant has no custom logo — callers fall back to a
     * styled business-name wordmark rather than stamping the generic app logo on
     * a tenant's customer-facing handout.
     */
    private function getLogoDataUri(): ?string
    {
        $logo = $this->tenant?->getSetting('branding.logo') ?? $this->tenant?->logo;

        if (! $logo || ! Storage::disk('public')->exists($logo)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($logo, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => null,
        };

        if ($mime === null) {
            return null;
        }

        try {
            $bytes = Storage::disk('public')->get($logo);
        } catch (\Throwable $e) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    /**
     * Get default branding when no tenant.
     */
    private function getDefaultBranding(): array
    {
        return [
            'business_name' => config('app.name', 'EventPro'),
            'tagline' => 'Professional Event Management',
            'logo' => asset('images/logo.svg'),
            'logo_dark' => asset('images/logo.svg'),
            'logo_pdf' => null,
            'favicon' => asset('images/favicon.svg'),
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#1E40AF',
                'accent' => '#10B981',
            ],
            'contact' => [
                'email' => config('mail.from.address'),
                'phone' => null,
                'address' => null,
            ],
            'social' => [],
        ];
    }

    /**
     * Whitelist a CSS color value. Returns the value only when it matches a safe
     * color shape (#hex, rgb()/rgba(), hsl()/hsla(), or a bare keyword); otherwise
     * the fallback. None of these shapes can contain the characters needed to break
     * out of a CSS value or the surrounding <style> element.
     */
    private function safeColor(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        $patterns = [
            '/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', // hex
            '/^rgba?\(\s*[0-9.,%\s\/]+\)$/i',                            // rgb()/rgba()
            '/^hsla?\(\s*[0-9.,%\s\/]+\)$/i',                            // hsl()/hsla()
            '/^[a-z]{1,30}$/i',                                          // named keyword
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return $value;
            }
        }

        return $fallback;
    }

    /**
     * Darken a hex color.
     */
    private function darkenColor(string $hex, int $percent): string
    {
        return $this->adjustBrightness($hex, -$percent);
    }

    /**
     * Lighten a hex color.
     */
    private function lightenColor(string $hex, int $percent): string
    {
        return $this->adjustBrightness($hex, $percent);
    }

    /**
     * Adjust color brightness.
     */
    private function adjustBrightness(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
