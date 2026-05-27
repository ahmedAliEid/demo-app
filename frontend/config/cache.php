<?php

declare(strict_types=1);

return [
    'default' => env('CACHE_STORE', 'file'),
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'null' => [
            'driver' => 'null',
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'demo_app_cache'),
];
