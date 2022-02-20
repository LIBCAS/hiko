<?php

namespace App\Http\Controllers;

use App\Models\Letter;

class LetterPreviewController extends Controller
{
    public function __invoke()
    {
        return view('pages.letters.preview', [
            'title' => __('hiko.letters_preview'),
            'letters' => Letter::with('identities', 'places', 'keywords')
                ->get()
                ->map(function ($letter) {
                    $identities = $letter->identities; // prevent modifying original values
                    $places = $letter->places;
                    $letter['identities_grouped'] = $identities->groupBy('pivot.role')->toArray();
                    $letter['places_grouped'] = $places->groupBy('pivot.role')->toArray();
                    return $letter;
                }),
        ]);
    }
}
