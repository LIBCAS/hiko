<?php

if (!function_exists('computeDate')) {
    function computeDate($letter)
    {
        if ($letter->date_month == 2 && $letter->date_day == 29) {
            $letter->date_day = 28; // 29th February is not a valid date in mysql
        }

        return implode('-', [
            $letter->date_year ? (string) $letter->date_year : '0001',
            $letter->date_month ? str_pad($letter->date_month, 2, '0', STR_PAD_LEFT) : '01',
            $letter->date_day ? str_pad($letter->date_day, 2, '0', STR_PAD_LEFT) : '01',
        ]);
    }
}

if (!function_exists('removeAccents')) {
    function removeAccents($string)
    {
        $transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: Lower(); :: NFC;', Transliterator::FORWARD);
        return $transliterator->transliterate($string);
    }
}

if (!function_exists('similar')) {
    function similar(string $string1, string $string2)
    {
        return levenshtein(
            trim(strtolower(removeAccents($string1))),
            trim(strtolower(removeAccents($string2))),
        ) <= 3;
    }
}
