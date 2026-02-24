<?php

use Carbon\Carbon;

if (!function_exists('computeDate')) {
    /**
     * Compute the canonical lower-bound date for sorting from the letter's partial date parts.
     * Returns 'Y-m-d' or null when year is unknown.
     *
     * @param  \App\Models\Letter  $letter
     * @return string|null
     */
    function computeDate($letter): ?string
    {
        // normalize inputs: '', '0', 0 => null
        $y = $letter->date_year;
        $m = $letter->date_month;
        $d = $letter->date_day;

        $y = ($y === '' || $y === 0 || $y === '0') ? null : $y;
        $m = ($m === '' || $m === 0 || $m === '0') ? null : $m;
        $d = ($d === '' || $d === 0 || $d === '0') ? null : $d;

        // Year is required to place it on a timeline.
        if (empty($y)) {
            return null;
        }

        // Default missing parts to the earliest possible within the known scope
        $m = $m ?: 1;
        $m = max(1, min(12, (int) $m));

        // Determine last valid day for that month/year and clamp
        $lastDay = Carbon::createFromDate((int) $y, (int) $m, 1)->endOfMonth()->day;
        $d = $d ? (int) $d : 1;
        $d = max(1, min($lastDay, $d));

        try {
            return Carbon::create((int) $y, (int) $m, (int) $d, 0, 0, 0)->toDateString();
        } catch (\Throwable $e) {
            // If parts are wildly invalid, treat as unknown
            return null;
        }
    }
}

if (!function_exists('removeAccents')) {
    function removeAccents($string)
    {
        if (!is_string($string)) {
            return '';
        }

        static $transliterator = null;

        if ($transliterator === null) {
            $transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD);
        }

        return $transliterator->transliterate($string);
    }
}

if (!function_exists('similar')) {
    function similar(string $string1, string $string2)
    {
        return levenshtein(
            trim(strtolower(str_replace(',', '', removeAccents($string1)))),
            trim(strtolower(str_replace(',', '', removeAccents($string2))))
        ) <= 3;
    }
}

if (!function_exists('calculateSimilarityPercentage')) {
    /**
     * Calculate similarity percentage between two strings using similar_text.
     * Returns a value between 0 and 100.
     *
     * @param string $string1
     * @param string $string2
     * @return float Similarity percentage (0-100)
     */
    function calculateSimilarityPercentage(string $string1, string $string2): float
    {
        if (empty($string1) || empty($string2)) {
            return 0.0;
        }

        // Normalize strings: remove accents, convert to lowercase, trim
        $normalized1 = trim(strtolower(str_replace(',', '', removeAccents($string1))));
        $normalized2 = trim(strtolower(str_replace(',', '', removeAccents($string2))));

        // Handle identical strings
        if ($normalized1 === $normalized2) {
            return 100.0;
        }

        // Calculate similarity using PHP's similar_text function
        $percent = 0.0;
        similar_text($normalized1, $normalized2, $percent);

        return round($percent, 2);
    }
}
