<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MergeSqlExports extends Command
{
    protected $signature = 'sql:merge';
    protected $description = 'Merge SQL export files into a single SQL import file';

    public function handle()
    {
        $exportDir = database_path('export');
        $importsCount = 0;

        $files = File::files($exportDir);

        $this->info("SQL export files found: " . count($files));

        foreach ($files as $file) {
            // Extract tenant name from the filename
            $filename = $file->getFilename();
            $tenantTablePrefix = explode('.', $filename)[0];

            // Fetch tenant using Eloquent
            $tenant = Tenant::where('table_prefix', $tenantTablePrefix)->first();

            if (!$tenant) {
                $this->error("No tenant found for the table prefix: {$tenantTablePrefix}");
                continue;
            }

            $content = File::get($file->getPathname());

            $content = str_replace('CREATE TABLE `', 'CREATE TABLE `' . $tenant->table_prefix . '__', $content);
            $content = str_replace('ALTER TABLE `', 'ALTER TABLE `' . $tenant->table_prefix . '__', $content);
            $content = str_replace('INSERT INTO `', 'INSERT INTO `' . $tenant->table_prefix . '__', $content);
            $content = str_replace('ADD CONSTRAINT `', 'ADD CONSTRAINT `' . $tenant->id . '_', $content);
            $content = str_replace(' REFERENCES `', ' REFERENCES `' . $tenant->table_prefix . '__', $content);

            File::put(database_path('import/' . $tenantTablePrefix . '.import.sql'), $content);
            $importsCount += 1;
        }

        $this->info('SQL import files generated:' . $importsCount);
    }
}
