<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GlobalLocationMergeController extends Controller
{
    public function __invoke()
    {
        return view('pages.locations.global-merge', [
            'title' => __('hiko.global_location_merging'),
        ]);
    }
}
