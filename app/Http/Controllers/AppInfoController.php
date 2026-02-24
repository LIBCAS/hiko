<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppInfoController extends Controller
{
    public function __invoke()
    {
        return view('pages.app.index', [
            'title' => __('hiko.application_info'),
        ]);
    }
}
