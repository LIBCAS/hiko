<?php

namespace App\Http\Controllers;

use App\Models\Letter;
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

    public function flushSearchIndex()
    {
        Letter::all()->unsearchable();
        Identity::all()->unsearchable();
    }

    public function buildSearchIndex()
    {
        Letter::all()->searchable();
        Identity::all()->searchable();
    }
}
