<?php

declare(strict_types=1);

return [
    'base_url' => env('DYNAMIC_QRIS_BASE_URL', 'https://qris.karuhundeveloper.com'),
    'api_key' => env('DYNAMIC_QRIS_API_KEY'),
    // HTTP timeout in seconds. Long polling automatically extends this default.
    'timeout' => (int) env('DYNAMIC_QRIS_TIMEOUT', 10),
    'connect_timeout' => (int) env('DYNAMIC_QRIS_CONNECT_TIMEOUT', 3),
];
