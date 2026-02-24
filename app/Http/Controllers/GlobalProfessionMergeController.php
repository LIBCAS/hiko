<?php

namespace App\Http\Controllers;

class GlobalProfessionMergeController extends Controller
{
    public function __invoke()
    {
        return view('pages.professions.global-merge', [
            'title' => __('hiko.global_profession_merging'),
        ]);
    }
}
