<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EventPro Default Settings
    |--------------------------------------------------------------------------
    |
    | These are the application-wide default settings that can be overridden
    | by tenant-specific settings.
    |
    */

    'defaults' => [
        'general' => [
            'business_name' => env('APP_NAME', 'EventPro'),
            'tagline' => 'Professional Event Management',
            'timezone' => env('EVENTPRO_DEFAULT_TIMEZONE', 'UTC'),
            'date_format' => 'd M Y',
            'time_format' => 'h:i A',
        ],

        'currency' => [
            'code' => env('EVENTPRO_DEFAULT_CURRENCY', 'LKR'),
            'symbol' => 'Rs',
            'position' => 'before',
            'decimal_places' => 2,
        ],

        'booking' => [
            'min_advance_days' => 7,
            'max_advance_days' => 365,
            'tentative_hold_hours' => 48,
            'require_deposit' => true,
            'deposit_percentage' => 25,
        ],

        'quotation' => [
            'validity_days' => 14,
            'auto_followup_days' => [3, 7, 14],
            'number_prefix' => 'QT',
        ],

        'payment' => [
            'installment_schedule' => [
                ['name' => 'Advance', 'percentage' => 25],
                ['name' => 'Second', 'percentage' => 35],
                ['name' => 'Final', 'percentage' => 40],
            ],
            'accepted_methods' => ['bank_transfer', 'credit_card', 'cash'],
        ],

        'cancellation' => [
            'policy' => [
                ['days_before' => 60, 'refund_percentage' => 50],
                ['days_before' => 30, 'refund_percentage' => 25],
                ['days_before' => 0, 'refund_percentage' => 0],
            ],
        ],

        'event_types' => [
            'enabled_types' => ['wedding', 'corporate', 'conference', 'birthday', 'other'],
            'custom_types' => [],
        ],

        'pricing' => [
            'tax_rate' => 0,
            'peak_season_surcharge' => 15,
            'peak_months' => [12, 1, 2],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Mode
    |--------------------------------------------------------------------------
    |
    | Supported modes: "single" (standalone), "column" (shared DB with tenant_id),
    | "database" (separate database per tenant)
    |
    */

    'tenancy_mode' => env('TENANCY_MODE', 'single'),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable features at the application level.
    |
    */

    'features' => [
        'multi_tenancy' => env('TENANCY_MODE', 'single') !== 'single',
        'client_portal' => true,
        'vendor_management' => true,
        'staff_scheduling' => true,
        'custom_fields' => true,
        'plugins' => true,
        'themes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Settings
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'max_file_size' => env('EVENTPRO_MAX_UPLOAD_SIZE', 10240), // KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public Demo Sandbox
    |--------------------------------------------------------------------------
    |
    | When enabled, the landing page offers a one-click "Try the demo" that
    | provisions an isolated, throwaway demo tenant. Demos expire after
    | `lifetime_minutes` and are reaped by the `demo:reap` schedule.
    |
    */

    'demo' => [
        'enabled'          => env('DEMO_MODE', false),
        'lifetime_minutes' => (int) env('DEMO_LIFETIME', 60),
        'max_live'         => (int) env('DEMO_MAX_LIVE', 50),
        'throttle'         => (int) env('DEMO_THROTTLE', 5), // new demos per IP per hour
    ],
];
