<?php

use App\Imports\UsersImport;
use App\Imports\ImagesImport;
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

// Inspire Command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Import Keywords Command
Artisan::command('hiko:import-keyword', function () {
    try {
        (new KeywordsImport)->import();
        $this->info('Keywords imported successfully.');
    } catch (Exception $e) {
        $this->error('Failed to import keywords: ' . $e->getMessage());
    }
})->purpose('Import keywords from previous version');

// Import Users Command
Artisan::command('hiko:import-users', function () {
    try {
        (new UsersImport)->import();
        $this->info('Users imported successfully.');
    } catch (Exception $e) {
        $this->error('Failed to import users: ' . $e->getMessage());
    }
})->purpose('Import users from previous version');

// Import Professions Command
Artisan::command('hiko:import-professions', function () {
    try {
        (new ProfessionsImport)->import();
        $this->info('Professions imported successfully.');
    } catch (Exception $e) {
        $this->error('Failed to import professions: ' . $e->getMessage());
    }
})->purpose('Import professions from previous version');

// Import Places Command
Artisan::command('hiko:import-places', function () {
    try {
        (new PlacesImport)->import();
        $this->info('Places imported successfully.');
    } catch (Exception $e) {
        $this->error('Failed to import places: ' . $e->getMessage());
    }
})->purpose('Import places from previous version');

// Import Identities Command
Artisan::command('hiko:import-identities', function () {
    try {
        (new IdentitiesImport)->import();
        $this->info('Identities imported successfully.');
    } catch (Exception $e) {
        $this->error('Failed to import identities: ' . $e->getMessage());
    }
})->purpose('Import identities from previous version');

// Import Letters Command with Prefix
Artisan::command('hiko:import-letters {prefix}', function ($prefix) {
    try {
        (new LettersImport)->import($prefix);
        $this->info("Letters imported successfully with prefix '{$prefix}'.");
    } catch (Exception $e) {
        $this->error("Failed to import letters with prefix '{$prefix}': " . $e->getMessage());
    }
})->purpose('Import letters from previous version');

// Import Media Command
Artisan::command('hiko:import-media', function () {
    try {
        (new ImagesImport)->import();
        $this->info('Media imported successfully.');
    } catch (Exception $e) {
        $this->error('Failed to import media: ' . $e->getMessage());
    }
})->purpose('Import letters media from previous version');

// Regenerate Locations Command
Artisan::command('hiko:regenerate-locations', function () {
    try {
        RegenerateLocations::dispatch();
        $this->info('RegenerateLocations job dispatched successfully.');
    } catch (Exception $e) {
        $this->error('Failed to dispatch RegenerateLocations job: ' . $e->getMessage());
    }
})->purpose('Regenerate locations');
