<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class DevToolsController extends Controller
{
    public function optimize()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
    }

    public function clear()
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
    }
}
