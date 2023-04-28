<?php

namespace App\Http\Controllers;

class AccountController extends Controller
{
    public function __invoke()
    {
        return view('pages.users.account', [
            'title' => __('hiko.settings'),
        ]);
    }
}
