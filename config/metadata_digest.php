<?php

return [
    /*
    | Delivery is deliberately opt-in because tenant user tables contain real
    | addresses. Keep this false until a production mail run is approved.
    */
    'enabled' => env('METADATA_DIGEST_ENABLED', false),

    'timezone' => env('METADATA_DIGEST_TIMEZONE', config('app.timezone', 'Europe/Prague')),
    'monthly_day' => (int) env('METADATA_DIGEST_MONTHLY_DAY', 1),
    'monthly_time' => env('METADATA_DIGEST_MONTHLY_TIME', '00:10'),
    'process_limit' => (int) env('METADATA_DIGEST_PROCESS_LIMIT', 5),
    'max_attempts' => (int) env('METADATA_DIGEST_MAX_ATTEMPTS', 3),
    'retry_after_minutes' => (int) env('METADATA_DIGEST_RETRY_AFTER_MINUTES', 15),
    'manual_max_days' => (int) env('METADATA_DIGEST_MANUAL_MAX_DAYS', 366),
    'max_attachment_bytes' => (int) env('METADATA_DIGEST_MAX_ATTACHMENT_BYTES', 20 * 1024 * 1024),
    'url_scheme' => env('METADATA_DIGEST_URL_SCHEME', 'https'),
];
