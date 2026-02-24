<?php

namespace App\Http\Controllers;

class GlobalIdentityMergeController extends Controller
{
    public function __invoke()
    {
        return view('pages.identities.global-merge', [
            'title' => __('hiko.global_identity_merging'),
        ]);
    }
}

