<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Domain;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {run_migrations} {prefix} {name} {main_character}';
    protected $description = 'Create a new tenant with the given name and domain';
    protected $appDomain;

    public function __construct()
    {
        parent::__construct();

        $this->appDomain = str_replace('https://', '', config('app.url'));
    }

    public function handle()
    {
        $runMigrations = strtolower($this->argument('run_migrations')) === 'y';
        $tenantPrefix = $this->argument('prefix');
        $tenantName = $this->argument('name');
        $tenantMainCharacterId = $this->argument('main_character');

        try {
            // Insert into tenants table
            DB::table('tenants')->insert([
                'name' => $tenantName,
                'table_prefix' => $tenantPrefix,
                'main_character' => $tenantMainCharacterId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get the last inserted ID (assuming your tenant table has auto-incrementing IDs)
            $newTenantId = DB::getPdo()->lastInsertId();

            // Insert into domains table
            DB::table('domains')->insert([
                'domain' => $tenantPrefix . '.' . $this->appDomain,
                'tenant_id' => $newTenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info('Tenant and domain have been created successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to create tenant and domain. Error: ' . $e->getMessage());
        }

        if ($runMigrations && $tenant) {
            try {
                tenancy()->initializeTenancy($tenant);

                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant',
                    '--force' => true   // Running migrations in production without confirmation
                ]);

                $this->info('Tenant-aware migrations successfully executed.');
            } catch (\Exception $e) {
                $this->error('Failed to run tenant-aware migrations. Error: ' . $e->getMessage());
            }
        }
    }
}
