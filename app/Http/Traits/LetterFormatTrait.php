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

        $title = "{$letter->pretty_date} ";
        $title .= $author ? $author['name'] . ' ' : '';
        $title .= $origin ? "({$origin['name']}) " : '';
        $title .= $recipient || $destination ? 'to ' : '';
        $title .= $recipient ? $recipient['name'] . ' ' : '';
        $title .= $destination ? "({$destination['name']}) " : '';

        return $title;
    }
}
