<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Letter;
use App\Models\Keyword;
use App\Models\Identity;
use App\Models\Location;
use App\Models\Profession;
use App\Models\KeywordCategory;
use App\Models\ProfessionCategory;
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
        Keyword::all()->unsearchable();
        KeywordCategory::all()->unsearchable();
        Place::all()->unsearchable();
        Profession::all()->unsearchable();
        ProfessionCategory::all()->unsearchable();
        Location::all()->unsearchable();
    }

    public function buildSearchIndex()
    {
        Letter::all()->searchable();
        Identity::all()->searchable();
        Keyword::all()->searchable();
        KeywordCategory::all()->searchable();
        Place::all()->searchable();
        Profession::all()->searchable();
        ProfessionCategory::all()->searchable();
        Location::all()->searchable();
    }
}
