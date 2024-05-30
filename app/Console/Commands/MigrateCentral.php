<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

class MigrateCentral extends Command
{
    protected $signature = 'migrate:central';
    protected $description = 'Run all central migrations';

    public function handle()
    {
        $migrationPath = database_path('migrations');
        $allFiles = Collection::make(glob($migrationPath . '/*.php'))->sortBy(function ($file) {
            return $file;
        });

        // Exclude tenant-specific migrations
        $filesToMigrate = $allFiles->filter(function ($path) {
            return !str_contains($path, '/tenant/');
        });

        if ($filesToMigrate->isEmpty()) {
            $this->info('No central migrations to run.');
            return;
        }

        foreach ($filesToMigrate as $file) {
            $relativePath = str_replace(base_path() . '/', '', $file);
            Artisan::call('migrate --path=' . $relativePath);
            $this->info('Migrated: ' . basename($file));
        }
    }
}
