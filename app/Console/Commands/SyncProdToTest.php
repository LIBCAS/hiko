<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Command to sync Global tables and Mapped Tenant tables
 * from Production to Local/Test database.
 */
class SyncProdToTest extends Command
{
    protected $signature = 'db:sync-prod {--yes : Skip confirmation}';
    protected $description = 'One-way sync of Global tables and Mapped Tenant tables from Production to Local/Test DB.';

    protected array $globalTables = [
        'global_identities',
        'global_identity_profession',
        'global_keyword_categories',
        'global_keywords',
        'global_locations',
        'global_places',
        'global_profession_categories',
        'global_professions',
        'religion_closure',
        'religion_translations',
        'religions',
    ];

    protected array $tenantTableSuffixes = [
        'duplicates',
        'identities',
        'identity_letter',
        'identity_profession',
        'identity_profession_category',
        'identity_religion',
        'keyword_categories',
        'keyword_letter',
        'keywords',
        'letter_place',
        'letter_user',
        'letters',
        'locations',
        'media',
        'places',
        'profession_categories',
        'professions',
    ];

    protected array $tenantMapping = [];    //

    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('NEVER run this in production!');
            return 1;
        }

        $configPath = base_path('tenant_mapping.json');

        if (!file_exists($configPath)) {
            $this->error("Configuration file not found: {$configPath}");
            $this->line("Please copy 'tenant_mapping.json.example' to 'tenant_mapping.json' and configure your prefixes.");
            return 1;
        }

        $jsonContent = file_get_contents($configPath);
        $this->tenantMapping = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($this->tenantMapping)) {
            $this->error("Invalid JSON in tenant_mapping.json or empty mapping.");
            return 1;
        }

        if (!$this->option('yes') && !$this->confirm('This will WIPE data in the LOCAL/TEST database. Continue?')) {
            return 0;
        }

        $this->info('Starting Sync...');
        $startTime = microtime(true);

        try {
            $prodConnection = DB::connection('production_sync');
            $localConnection = DB::connection('local_sync');

            $prodConnection->getPdo();
            $localConnection->getPdo();

            $prodDb = $prodConnection->getDatabaseName();
            $localDb = $localConnection->getDatabaseName();
            Log::info("SYNC START. Prod DB: [{$prodDb}], Local DB: [{$localDb}]");
        } catch (Throwable $e) {
            Log::error("SYNC ERROR: Could not connect to databases. " . $e->getMessage());
            $this->error("Could not connect to databases. " . $e->getMessage());
            return 1;
        }

        DB::connection('local_sync')->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->syncGlobalTables();
            $this->syncTenantTables();

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Sync completed in {$duration} seconds.");
            Log::info("SYNC COMPLETED in {$duration} seconds.");

        } catch (Throwable $e) {
            Log::error("SYNC EXCEPTION: " . $e->getMessage());
            $this->error($e->getMessage());
            return 1;
        } finally {
            DB::connection('local_sync')->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return 0;
    }

    protected function syncGlobalTables()
    {
        $this->info('Syncing Global Tables...');
        $bar = $this->output->createProgressBar(count($this->globalTables));

        foreach ($this->globalTables as $table) {
            $prodExists = Schema::connection('production_sync')->hasTable($table);
            $localExists = Schema::connection('local_sync')->hasTable($table);

            if (!$prodExists) {
                Log::warning("SYNC GLOBAL: Table '{$table}' NOT FOUND in Production.");
                $bar->advance(); continue;
            }
            if (!$localExists) {
                Log::warning("SYNC GLOBAL: Table '{$table}' NOT FOUND in Local.");
                $bar->advance(); continue;
            }

            Log::info("SYNC GLOBAL: Processing '{$table}'...");

            DB::connection('local_sync')->table($table)->truncate();

            // Dynamic sort column
            $orderBy = $this->getSortColumn($table);

            $count = 0;
            DB::connection('production_sync')->table($table)
                ->orderBy($orderBy)
                ->chunk(1000, function ($rows) use ($table, &$count) {
                    $data = $rows->map(fn ($row) => (array) $row)->toArray();
                    DB::connection('local_sync')->table($table)->insert($data);
                    $count += count($data);
                });

            Log::info("SYNC GLOBAL: Copied {$count} rows to '{$table}'.");
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function syncTenantTables()
    {
        $this->info('Syncing Tenant Tables...');

        foreach ($this->tenantMapping as $prodPrefix => $testPrefix) {

            foreach ($this->tenantTableSuffixes as $suffix) {
                $sourceTable = "{$prodPrefix}__{$suffix}";
                $targetTable = "{$testPrefix}__{$suffix}";

                $prodExists = Schema::connection('production_sync')->hasTable($sourceTable);
                $localExists = Schema::connection('local_sync')->hasTable($targetTable);

                if (!$prodExists) {
                    Log::warning("SYNC TENANT: Prod table '{$sourceTable}' NOT FOUND.");
                    continue;
                }
                if (!$localExists) {
                    Log::warning("SYNC TENANT: Local table '{$targetTable}' NOT FOUND.");
                    continue;
                }

                Log::info("SYNC TENANT: Syncing {$sourceTable} -> {$targetTable}...");

                DB::connection('local_sync')->table($targetTable)->truncate();

                // Dynamic sort column
                $orderBy = $this->getSortColumn($sourceTable);

                $count = 0;
                DB::connection('production_sync')->table($sourceTable)
                    ->orderBy($orderBy)
                    ->chunk(1000, function ($rows) use ($targetTable, &$count) {
                        $data = $rows->map(fn ($row) => (array) $row)->toArray();
                        DB::connection('local_sync')->table($targetTable)->insert($data);
                        $count += count($data);
                    });

                Log::info("SYNC TENANT: Copied {$count} rows to {$targetTable}.");
            }
        }
    }

    /**
     * Determine a valid column to sort by for chunking.
     * Uses 'id' if available, otherwise the first column in the schema.
     */
    private function getSortColumn($table)
    {
        $columns = Schema::connection('production_sync')->getColumnListing($table);

        if (in_array('id', $columns)) {
            return 'id';
        }

        // Fallback for pivot tables or closure tables
        return $columns[0] ?? null;
    }
}
