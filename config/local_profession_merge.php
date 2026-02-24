<?php

return [
    'name_similarity_threshold' => 80,

    'default_criteria' => [
        'name_similarity',
    ],

    'criteria_order' => [
        'same_category',
        'name_similarity',
    ],
];
