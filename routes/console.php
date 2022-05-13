<?php

use App\Imports\UsersImport;
use App\Imports\PlacesImport;
use App\Imports\KeywordsImport;
use App\Imports\ProfessionsImport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('import:keyword', function () {
    $this->comment((new KeywordsImport)->import());
})->purpose('Import keywords from previous version');

Artisan::command('import:users', function () {
    $this->comment((new UsersImport)->import());
})->purpose('Import users from previous version');

Artisan::command('import:professions', function () {
    $this->comment((new ProfessionsImport)->import());
})->purpose('Import professions from previous version');

Artisan::command('import:places', function () {
    $this->comment((new PlacesImport)->import());
})->purpose('Import places from previous version');
