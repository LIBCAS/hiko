<?php

return [
    'name_similarity_threshold' => 80,

    'default_criteria' => [
        'viaf_id',
        'name_similarity',
        // 'dates', // Birth/Death years
    ],

    'criteria_order' => [
        'viaf_id',
        'dates',
        'name_similarity',
    ],
];
