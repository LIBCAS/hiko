<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global Identity Strict Merge Configuration
    |--------------------------------------------------------------------------
    |
    | Defaults for strict global-to-global identity merging and the similarity
    | candidate scanner on /identities/global-strict-merge.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Similarity Candidate Scanner
    |--------------------------------------------------------------------------
    */
    'similarity_candidate_scan' => [
        'default_criteria' => ['name_similarity', 'date_similarity'],

        'name_similarity_threshold' => 80,

        'birth_year_tolerance' => 5,

        'death_year_tolerance' => 5,

        'year_tolerance_options' => [0, 1, 2, 5],

        'group_limit' => 300,
    ],
];
