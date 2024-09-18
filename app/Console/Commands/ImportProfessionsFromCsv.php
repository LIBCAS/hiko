<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class ImportProfessionsFromCsv extends Command
{
    protected $signature = 'import:professions {file}';
    protected $description = 'Import professions and categories from CSV file';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->error('CSV file not found or not readable.');
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0); // assuming the first row contains the headers

        $records = $csv->getRecords();
        DB::beginTransaction();

        try {
            foreach ($records as $record) {
                $category = DB::table('global_profession_categories')
                    ->updateOrInsert(
                        ['name' => $record['profession_category_en']],
                        ['name' => $record['profession_category_en']]
                    );

                DB::table('global_professions')
                    ->insert([
                        'name' => $record['profession_en'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            DB::commit();
            $this->info('CSV data imported successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error importing data: ' . $e->getMessage());
        }
    }
}
