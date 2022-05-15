<?php

use App\Imports\UsersImport;
use App\Imports\PlacesImport;
use App\Imports\LettersImport;
use App\Imports\KeywordsImport;
use App\Imports\IdentitiesImport;
use App\Imports\ProfessionsImport;
use App\Jobs\RegenerateLocations;
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

Artisan::command('hiko:import-keyword', function () {
    $this->comment((new KeywordsImport)->import());
})->purpose('Import keywords from previous version');

Artisan::command('hiko:import-users', function () {
    $this->comment((new UsersImport)->import());
})->purpose('Import users from previous version');

Artisan::command('hiko:import-professions', function () {
    $this->comment((new ProfessionsImport)->import());
})->purpose('Import professions from previous version');

Artisan::command('hiko:import-places', function () {
    $this->comment((new PlacesImport)->import());
})->purpose('Import places from previous version');

Artisan::command('hiko:import-identities', function () {
    $this->comment((new IdentitiesImport)->import());
})->purpose('Import identities from previous version');

Artisan::command('hiko:import-letters', function () {
    $this->comment((new LettersImport)->import());
})->purpose('Import letters from previous version');

Artisan::command('hiko:regenerate-locations', function () {
    RegenerateLocations::dispatch();
    $this->comment('OK');
})->purpose('Regenerate locations');
