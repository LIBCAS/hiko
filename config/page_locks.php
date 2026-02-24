<?php

return [
    'ttl_seconds' => (int) env('PAGE_LOCK_TTL_SECONDS', 60),
    'heartbeat_seconds' => (int) env('PAGE_LOCK_HEARTBEAT_SECONDS', 15),
    'grace_seconds' => (int) env('PAGE_LOCK_GRACE_SECONDS', 5),

    // Resource type => required ability
    'abilities' => [
        'identity_edit' => 'manage-metadata',
        'identity_local_merge' => 'manage-metadata',
        'identity_global_merge' => 'manage-users',
        'global_identity_edit' => 'manage-users',

        'letter_edit' => 'manage-metadata',

        'place_edit' => 'manage-metadata',
        'place_local_merge' => 'manage-metadata',
        'place_global_merge' => 'manage-users',
        'global_place_edit' => 'manage-users',

        'keyword_edit' => 'manage-metadata',
        'keyword_local_merge' => 'manage-metadata',
        'keyword_global_merge' => 'manage-users',
        'global_keyword_edit' => 'manage-users',
        'keyword_category_edit' => 'manage-metadata',
        'global_keyword_category_edit' => 'manage-users',

        'profession_edit' => 'manage-metadata',
        'profession_local_merge' => 'manage-metadata',
        'profession_global_merge' => 'manage-users',
        'global_profession_edit' => 'manage-users',
        'profession_category_edit' => 'manage-metadata',
        'global_profession_category_edit' => 'manage-users',

        'location_edit' => 'manage-metadata',
        'location_local_merge' => 'manage-metadata',
        'location_global_merge' => 'manage-users',
        'global_location_edit' => 'manage-users',

        'religions_admin' => 'manage-metadata',
    ],
];
