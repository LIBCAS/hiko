<?php

return [
    'geonames_username' => env('GEONAMES_USERNAME', ''),
    'main_character' => env('MAIN_CHARACTER_ID'),
    'metadata_default_locale' => env('METADATA_DEFAULT_LOCALE', 'cs'),
    'metadata_records_per_page' => env('METADATA_RECORDS_PER_PAGE', 25),
    'version' => env('APP_VERSION', 'dev'),
    'show_watermark' => env('SHOW_WATERMARK', true),
    'public_url' => env('PUBLIC_LETTERS_URL', ''),
];
