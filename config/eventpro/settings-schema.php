<?php

/*
|--------------------------------------------------------------------------
| EventPro Settings Schema
|--------------------------------------------------------------------------
|
| This file defines the schema for all configurable settings in EventPro.
| Each setting includes its type, validation rules, and default value.
|
*/

return [
    'general' => [
        'business_name' => [
            'type' => 'text',
            'required' => true,
            'label' => 'Business Name',
            'description' => 'Your business or venue name',
        ],
        'tagline' => [
            'type' => 'text',
            'label' => 'Tagline',
            'description' => 'A short tagline or slogan',
        ],
        'timezone' => [
            'type' => 'select',
            'options' => 'timezones',
            'default' => 'UTC',
            'label' => 'Timezone',
        ],
        'date_format' => [
            'type' => 'select',
            'options' => ['d M Y', 'M d, Y', 'Y-m-d', 'd/m/Y', 'm/d/Y'],
            'default' => 'd M Y',
            'label' => 'Date Format',
        ],
        'time_format' => [
            'type' => 'select',
            'options' => ['h:i A', 'H:i'],
            'default' => 'h:i A',
            'label' => 'Time Format',
        ],
    ],

    'currency' => [
        'code' => [
            'type' => 'select',
            'options' => 'currencies',
            'default' => 'LKR',
            'label' => 'Currency Code',
        ],
        'symbol' => [
            'type' => 'text',
            'default' => 'Rs',
            'label' => 'Currency Symbol',
        ],
        'position' => [
            'type' => 'select',
            'options' => ['before', 'after'],
            'default' => 'before',
            'label' => 'Symbol Position',
        ],
        'decimal_places' => [
            'type' => 'number',
            'default' => 2,
            'min' => 0,
            'max' => 4,
            'label' => 'Decimal Places',
        ],
    ],

    'booking' => [
        'min_advance_days' => [
            'type' => 'number',
            'default' => 7,
            'min' => 0,
            'label' => 'Minimum Advance Days',
            'description' => 'Minimum days in advance for booking',
        ],
        'max_advance_days' => [
            'type' => 'number',
            'default' => 365,
            'min' => 30,
            'label' => 'Maximum Advance Days',
            'description' => 'Maximum days in advance for booking',
        ],
        'tentative_hold_hours' => [
            'type' => 'number',
            'default' => 48,
            'min' => 1,
            'label' => 'Tentative Hold Hours',
            'description' => 'Hours to hold a tentative booking',
        ],
        'require_deposit' => [
            'type' => 'boolean',
            'default' => true,
            'label' => 'Require Deposit',
        ],
        'deposit_percentage' => [
            'type' => 'number',
            'default' => 25,
            'min' => 0,
            'max' => 100,
            'label' => 'Deposit Percentage',
        ],
    ],

    'quotation' => [
        'validity_days' => [
            'type' => 'number',
            'default' => 14,
            'min' => 1,
            'label' => 'Quote Validity Days',
        ],
        'auto_followup_days' => [
            'type' => 'array',
            'default' => [3, 7, 14],
            'label' => 'Auto Follow-up Days',
            'description' => 'Days after quote to send follow-up',
        ],
        'number_prefix' => [
            'type' => 'text',
            'default' => 'QT',
            'label' => 'Quote Number Prefix',
        ],
    ],

    'payment' => [
        'installment_schedule' => [
            'type' => 'json',
            'default' => [
                ['name' => 'Advance', 'percentage' => 25],
                ['name' => 'Second', 'percentage' => 35],
                ['name' => 'Final', 'percentage' => 40],
            ],
            'label' => 'Payment Installments',
        ],
        'accepted_methods' => [
            'type' => 'multiselect',
            'options' => ['bank_transfer', 'credit_card', 'cash', 'cheque', 'online_payment'],
            'default' => ['bank_transfer', 'credit_card', 'cash'],
            'label' => 'Accepted Payment Methods',
        ],
    ],

    'cancellation' => [
        'policy' => [
            'type' => 'json',
            'default' => [
                ['days_before' => 60, 'refund_percentage' => 50],
                ['days_before' => 30, 'refund_percentage' => 25],
                ['days_before' => 0, 'refund_percentage' => 0],
            ],
            'label' => 'Cancellation Policy',
        ],
    ],

    'event_types' => [
        'enabled_types' => [
            'type' => 'multiselect',
            'options' => ['wedding', 'corporate', 'conference', 'birthday', 'anniversary', 'graduation', 'other'],
            'default' => ['wedding', 'corporate', 'conference', 'birthday', 'other'],
            'label' => 'Enabled Event Types',
        ],
        'custom_types' => [
            'type' => 'array',
            'default' => [],
            'label' => 'Custom Event Types',
        ],
    ],

    'branding' => [
        'primary_color' => [
            'type' => 'color',
            'default' => '#3B82F6',
            'label' => 'Primary Color',
        ],
        'secondary_color' => [
            'type' => 'color',
            'default' => '#1E40AF',
            'label' => 'Secondary Color',
        ],
    ],

    'notifications' => [
        'email_notifications' => [
            'type' => 'boolean',
            'default' => true,
            'label' => 'Email Notifications',
        ],
        'sms_notifications' => [
            'type' => 'boolean',
            'default' => false,
            'label' => 'SMS Notifications',
        ],
    ],
];
