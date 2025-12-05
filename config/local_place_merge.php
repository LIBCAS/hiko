<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global Place Merge Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains default values for the global place merging feature.
    | All threshold and tolerance values can be adjusted here in one place.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Name Similarity Threshold (%)
    |--------------------------------------------------------------------------
    |
    | Default threshold for name similarity matching.
    | Value between 0-100 where 100 means names must be identical.
    | Used for both "name_similarity" and "country_and_name" criteria.
    |
    */
    'name_similarity_threshold' => 80,

    /*
    |--------------------------------------------------------------------------
    | Country and Name Similarity Threshold (%)
    |--------------------------------------------------------------------------
    |
    | Default threshold for the combined country and name similarity criterion.
    | Value between 0-100. Both country must match AND names must be similar
    | above this threshold.
    |
    */
    'country_and_name_threshold' => 80,

    /*
    |--------------------------------------------------------------------------
    | Latitude Tolerance
    |--------------------------------------------------------------------------
    |
    | Default tolerance for latitude matching (in degrees).
    | Places within this latitude difference are considered matching.
    |
    */
    'latitude_tolerance' => 0.1,

    /*
    |--------------------------------------------------------------------------
    | Longitude Tolerance
    |--------------------------------------------------------------------------
    |
    | Default tolerance for longitude matching (in degrees).
    | Places within this longitude difference are considered matching.
    |
    */
    'longitude_tolerance' => 0.1,

    /*
    |--------------------------------------------------------------------------
    | Default Criteria
    |--------------------------------------------------------------------------
    |
    | Default merge criterion to use when page first loads.
    | Should be the strongest/most reliable criterion.
    |
    */
    'default_criteria' => ['geoname_id', 'alternative_names', 'country_and_name', 'name_similarity', 'coordinates'],

    /*
    |--------------------------------------------------------------------------
    | Criteria Strength Order
    |--------------------------------------------------------------------------
    |
    | Order of criteria from strongest to weakest.
    | When multiple criteria match, the strongest one is reported as the reason.
    |
    */
    'criteria_order' => [
        'geoname_id',           // Strongest - exact ID match
        'alternative_names',    // Exact name match in alternatives
        'country_and_name',     // Country match + name similarity
        'name_similarity',      // Name similarity only
        'coordinates',          // Coordinate-based matching (both lat AND lon)
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview Pagination
    |--------------------------------------------------------------------------
    |
    | Number of results to show per page in the merge preview.
    |
    */
    'preview_per_page' => 25,
];
