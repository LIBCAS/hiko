<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReligionAdminController extends Controller
{
    public function index(Request $request)
    {
        return view('pages.religions.index', [
            'title' => __('hiko.religions')
        ]);
    }
}
