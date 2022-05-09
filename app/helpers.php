<?php

if (!function_exists('computeDate')) {
    function computeDate($letter)
    {
        return implode('-', [
            $letter->date_year ? (string) $letter->date_year : '0001',
            $letter->date_month ? str_pad($letter->date_month, 2, '0', STR_PAD_LEFT) : '01',
            $letter->date_day ? str_pad($letter->date_day, 2, '0', STR_PAD_LEFT) : '01',
        ]);
    }
}