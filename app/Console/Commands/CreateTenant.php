<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {run_migrations} {prefix} {name} {main_character}';
    protected $description = 'Create a new tenant with the given name and domain';
    protected $appDomain;

    public function __construct()
    {
        parent::__construct();

        $this->appDomain = str_replace('https://', '', config('app.url_prod'));
        $this->appDomain = str_replace('http://', '', $this->appDomain);
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

            // create tenant related storage_path directories
            if (!File::exists(storage_path($tenantPrefix))) {
                File::makeDirectory(storage_path($tenantPrefix), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/app'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/app'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/app/imports'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/app/imports'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/app/livewire-tmp'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/app/livewire-tmp'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/app/public'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/app/public'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/debugbar'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/debugbar'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/framework'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/framework'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/framework/cache'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/framework/cache'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/framework/cache/data'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/framework/cache/data'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/framework/sessions'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/framework/sessions'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/framework/testing'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/framework/testing'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/framework/views'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/framework/views'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/indexes'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/indexes'), 0755, false);
            }

            if (!File::exists(storage_path($tenantPrefix . '/logs'))) {
                File::makeDirectory(storage_path($tenantPrefix . '/logs'), 0755, false);
            }

            // create tenant related public_path directories
            if (!File::exists(public_path('storage'))) {
                File::makeDirectory(public_path('storage'), 0755, false);
            }

            // create a symlink pointing from `public/storage/tenantPrefix` to `storage/tenantPrefix/app/public`
            if (!File::exists(public_path('storage/' . $tenantPrefix))) {
                $process = new Process(['ln', '-s', "../../storage/$tenantPrefix/app/public/", "./public/storage/$tenantPrefix"]);
                $process->run();

                if (!$process->isSuccessful()) {
                    $this->error("Failed to create a symlink pointing from '/public/storage/$tenantPrefix' to '/storage/$tenantPrefix/app/public'");
                }
            }

            // Get the newly created tenant
            $tenant = Tenant::find($newTenantId);

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
