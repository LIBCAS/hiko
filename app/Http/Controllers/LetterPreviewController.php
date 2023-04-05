<?php

namespace App\Http\Controllers;

use App\Models\Letter;

class LetterPreviewController extends Controller
{
    public function __invoke()
    {
        // FIX: temporary hack
        ini_set('memory_limit', '256M');

        return view('pages.letters.preview', [
            'title' => __('hiko.letters_preview'),
            'letters' => Letter::with('identities', 'places', 'keywords')
                ->get()
                ->map(function ($letter) {
                    $letter['identities_grouped'] = $letter->identities->groupBy('pivot.role')->toArray();
                    $letter['places_grouped'] = $letter->places->groupBy('pivot.role')->toArray();
                    return $letter;
                }),
        ]);
    }
}
