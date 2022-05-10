<?php

namespace App\Http\Controllers;

class LanguageController extends Controller
{
    public function __invoke($lang)
    {
        if (in_array($lang, ['cs', 'en'])) {
            session(['locale' => $lang]);
        }

        return redirect()->back();
    }
}
