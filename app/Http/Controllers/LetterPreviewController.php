<?php

namespace App\Http\Controllers;

use App\Models\Letter;

class LetterPreviewController extends Controller
{
    public function __invoke()
    {
        return view('pages.letters.preview', [
            'title' => __('hiko.letters_preview'),
            'letters' => Letter::with('identities', 'places', 'keywords')->get(),
        ]);
    }
}
