<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CreateTenantDirs extends Command
{
    protected $signature = 'tenant:create-dirs {prefix?}';
    protected $description = 'Creates tenant-related public_path and storage_path directories for all tenants or only for one specific tenant based on its table_prefix value';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $tenantPrefix = $this->argument('prefix');

        try {

            if ($tenantPrefix) {
                if (Tenant::where('table_prefix', $tenantPrefix)->exists()) {
                    $this->createDirs($tenantPrefix);
                    $this->info($tenantPrefix . ' tenant-related directories successfully created.');
                } else {
                    $this->info('No tenant with the ' . $tenantPrefix . ' prefix found.');
                }
            } else {
                $tenantPrefixes = Tenant::pluck('table_prefix')->toArray();

                foreach ($tenantPrefixes as $prefix) {
                    $this->createDirs($prefix);
                    $this->info($prefix . ' tenant-related directories successfully created.');
                }
            }
        } catch (\Exception $e) {
            $this->error('Failed to create tenant-related directories. Error: ' . $e->getMessage());
        }
    }

    protected function createDirs($tenantPrefix) {
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
    }

    // To delete all tenanat-related storage_path directories while testing:
    // cd ./storage; find . -maxdepth 1 ! -name '.' ! -name 'debugbar' ! -name 'framework' ! -name 'indexes' ! -name 'logs' -exec rm -rf {} +;
}
