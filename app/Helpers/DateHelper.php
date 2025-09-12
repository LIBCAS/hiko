<?php

/**
 * Helper class to derive date bounds from partial Y/M/D inputs.
 */

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Derive the start (lower) bound from possibly-partial Y/M/D.
     * Y only => Y-01-01; Y+M => Y-M-01; Y+M+D => Y-M-D; null year => null.
     *
     * @param  string|int|null  $y - year
     * @param  string|int|null  $m - month
     * @param  string|int|null  $d - day
     * @return Carbon|null
     */
    public static function deriveStartBoundDate($y, $m, $d): ?Carbon
    {
        if (empty($y)) {
            return null;
        }
        $yy = (int) $y;
        $mm = $m ? max(1, min(12, (int)$m)) : 1;

        // day: default to 1 if missing; clamp to month last day
        $lastDay = Carbon::create($yy, $mm, 1)->endOfMonth()->day;
        $dd = $d ? max(1, min($lastDay, (int)$d)) : 1;

        try {
            return Carbon::create($yy, $mm, $dd, 0, 0, 0);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Derive the end (upper) bound from possibly-partial Y/M/D.
     * Y only => Y-12-31; Y+M => Y-M-(last day); Y+M+D => Y-M-D; null year => null.
     *
     * @param  string|int|null  $y - year
     * @param  string|int|null  $m - month
     * @param  string|int|null  $d - day
     * @return Carbon|null
     */
    public static function deriveEndBoundDate($y, $m, $d): ?Carbon
    {
        if (empty($y)) {
            return null;
        }
        $yy = (int) $y;
        $mm = $m ? max(1, min(12, (int)$m)) : 12;

        // if day missing, take month last day; otherwise clamp to last day
        $lastDay = Carbon::create($yy, $mm, 1)->endOfMonth()->day;
        $dd = $d ? max(1, min($lastDay, (int)$d)) : $lastDay;

        try {
            return Carbon::create($yy, $mm, $dd, 23, 59, 59);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
