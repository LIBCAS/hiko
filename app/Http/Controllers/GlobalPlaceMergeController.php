<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class GlobalPlaceMergeController extends Controller
{
    public function index(): View
    {
        return view('pages.places.global-merge', [
            'title' => __('hiko.global_place_merging'),
        ]);
    }
}
