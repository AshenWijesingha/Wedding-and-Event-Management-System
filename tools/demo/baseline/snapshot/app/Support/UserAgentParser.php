<?php

namespace App\Support;

/**
 * Minimal, dependency-free User-Agent describer for the active-sessions list.
 * Good enough to help a user recognise their own devices ("Chrome on Windows");
 * not a full UA database.
 */
class UserAgentParser
{
    public static function describe(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown device';
        }

        return self::browser($userAgent) . ' on ' . self::platform($userAgent);
    }

    private static function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Unknown browser',
        };
    }

    private static function platform(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };
    }
}
