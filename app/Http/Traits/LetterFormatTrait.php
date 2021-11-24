<?php

namespace App\Http\Traits;

trait LetterFormatTrait
{
    public function formatLetterName($letter)
    {
        $author = isset($letter->identities['author']) ? $letter->identities['author'][0] : [];
        $recipient = isset($letter->identities['recipient']) ? $letter->identities['recipient'][0] : [];
        $origin = isset($letter->places['origin']) ? $letter->places['origin'][0] : [];
        $destination = isset($letter->places['destination']) ? $letter->places['destination'][0] : [];

        $title = '';
        $title .= $letter->date_day ? $letter->date_day . '. ' : '? ';
        $title .= $letter->date_month ? $letter->date_month . '. ' : '? ';
        $title .= $letter->date_year ? $letter->date_year . ' ' : '? ';
        $title .= $author ? $author['name'] . ' ' : '';
        $title .= $origin ? "({$origin['name']}) " : '';
        $title .= $recipient || $destination ? 'to ' : '';
        $title .= $recipient ? $recipient['name'] . ' ' : '';
        $title .= $destination ? "({$destination['name']}) " : '';

        return $title;
    }

    public function formatLetterDate($day, $month, $year)
    {
        $day = $day && $day != 0 ? $day : '?';
        $month = $month && $month != 0 ? $month : '?';
        $year = $year && $year != 0 ? $year : '????';

        if ($year == '????' && $month == '?' && $day == '?') {
            return '?';
        }

        return "{$day}/{$month}/{$year}";
    }
}
