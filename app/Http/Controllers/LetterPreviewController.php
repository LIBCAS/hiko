<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Http\Traits\LetterFormatTrait;

class LetterPreviewController extends Controller
{
    use LetterFormatTrait;

    public function __invoke()
    {
        $letters = Letter::with('identities', 'places')->get();

        return view('pages.letters.preview', [
            'title' => __('hiko.letters_preview'),
            'letters' => $letters->map(function ($letter) {
                $letter->identities = $letter->identities->groupBy('pivot.role')->toArray();
                $letter->places = $letter->places->groupBy('pivot.role')->toArray();
                $letter->name = $this->formatLetterName($letter);
                return $letter;
            }),
        ]);
    }
}
