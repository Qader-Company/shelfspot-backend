<?php

return [
    'enabled' => env('SHELSPOT_CACHE_ENABLED', true),

    'store' => env('SHELSPOT_CACHE_STORE', env('CACHE_STORE', 'redis')),

    'locales' => ['ar', 'en'],

    'groups' => [
        'reports' => env('SHELSPOT_CACHE_REPORTS_ENABLED', false),
        'catalog' => env('SHELSPOT_CACHE_CATALOG_ENABLED', false),
        'platform_settings' => env('SHELSPOT_CACHE_PLATFORM_SETTINGS_ENABLED', false),
    ],

    'reports' => [
        'admin_dashboard' => [
            'fresh_seconds' => 60,
            'stale_seconds' => 300,
        ],
        'company_dashboard' => [
            'fresh_seconds' => 60,
            'stale_seconds' => 300,
        ],
    ],

    'reference_data' => [
        'product_filter_options_seconds' => 900,
        'platform_settings_seconds' => 3600,
        'services_seconds' => 1800,
    ],
];
