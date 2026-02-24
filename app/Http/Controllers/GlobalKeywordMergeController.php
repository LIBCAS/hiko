<?php

namespace App\Http\Controllers;

class GlobalKeywordMergeController extends Controller
{
    public function __invoke()
    {
        return view('pages.keywords.global-merge', [
            'title' => __('hiko.global_keyword_merging'),
        ]);
    }
}
