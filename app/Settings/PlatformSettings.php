<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PlatformSettings extends Settings
{
    public string $platform_name;

    public ?int $default_plan_id;

    public bool $signups_enabled;

    public ?string $support_email;

    public static function group(): string
    {
        return 'platform';
    }
}
