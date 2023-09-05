<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PrepareOldSqlImports extends Command
{
    protected $signature = 'prepare:imports';
    protected $description = 'Prepare old SQL exports as new SQL imports';

    public function handle()
    {
        $exportDir = database_path('export');

        $files = File::files($exportDir);

        if (count($files) > 0) {
            $this->info('SQL export files found: ' . count($files));
        } else {
            $this->error('No SQL export files found.');
            return false;
        }

        $importDir = database_path('import');
        $importsCount = 0;

        if (!File::exists($importDir)) {
            File::makeDirectory($importDir, 0755, false);
        }

        foreach ($files as $file) {
            // Extract tenant name from the filename
            $filename = $file->getFilename();
            $tenantTablePrefix = explode('.', $filename)[0];
            $importFilePath = database_path('import/' . $tenantTablePrefix . '.import.sql');

            // Fetch tenant using Eloquent
            $tenant = Tenant::where('table_prefix', $tenantTablePrefix)->first();

            if (!$tenant) {
                $this->error('No tenant found for the table prefix: ' . $tenantTablePrefix);
                continue;
            }

            $content = File::get($file->getPathname());

            $content = str_replace('CREATE TABLE `', 'CREATE TABLE `' . $tenant->table_prefix . '__', $content);
            $content = str_replace('ALTER TABLE `', 'ALTER TABLE `' . $tenant->table_prefix . '__', $content);
            $content = str_replace('INSERT INTO `', 'INSERT INTO `' . $tenant->table_prefix . '__', $content);
            $content = str_replace('ADD CONSTRAINT `', 'ADD CONSTRAINT `' . $tenant->id . '_', $content);
            $content = str_replace(' REFERENCES `', ' REFERENCES `' . $tenant->table_prefix . '__', $content);

            File::put($importFilePath, $content);

            // Zip the SQL file
            $zipFilePath = database_path('import/' . $tenantTablePrefix . '.import.sql.zip');
            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($importFilePath, $tenantTablePrefix . '.import.sql');
                $zip->close();

                // Delete the original SQL file after zipping
                File::delete($importFilePath);
            } else {
                $this->error('Failed to create zip for: ' . $tenantTablePrefix);
            }

            $importsCount += 1;
        }

        $this->info('SQL import files generated:' . $importsCount);
    }
}
