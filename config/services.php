<?php

return [
    'name' => env('APP_NAME', 'Laravel'),
    'manifest' => [
        'background_color' => '#ffffff',
        'display' => 'standalone',
        'name' => env('APP_NAME', 'EventPro'),
        'short_name' => 'EventPro',
        'theme_color' => '#6366f1',
    ],

    'payhere' => [
        'merchant_id' => env('PAYHERE_MERCHANT_ID'),
        'merchant_secret' => env('PAYHERE_MERCHANT_SECRET'),
        'sandbox' => env('PAYHERE_SANDBOX', true),
        'currency' => env('PAYHERE_CURRENCY', 'LKR'),
    ],
];
