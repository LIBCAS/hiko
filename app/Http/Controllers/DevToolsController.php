<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class DevToolsController extends Controller
{
    public function cache()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
    }

    public function clear()
    {
        Artisan::call('optimize:clear');
    }
}
