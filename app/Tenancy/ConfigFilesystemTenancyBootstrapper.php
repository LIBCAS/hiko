<?php

namespace App\Tenancy;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class ConfigFilesystemTenancyBootstrapper extends FilesystemTenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        $suffix = $tenant->table_prefix;

        // storage_path()
        if ($this->app['config']['tenancy.filesystem.suffix_storage_path'] ?? true) {
            $this->app->useStoragePath($this->originalPaths['storage'] . "/{$suffix}");
            $this->ensureTenantStorageDirectories();
        }

        // asset()
        if ($this->app['config']['tenancy.filesystem.asset_helper_tenancy'] ?? true) {
            if ($this->originalPaths['asset_url']) {
                $this->app['config']['app.asset_url'] = ($this->originalPaths['asset_url'] ?? $this->app['config']['app.url']) . "/$suffix";
                $this->app['url']->setAssetRoot($this->app['config']['app.asset_url']);
            } else {
                $this->app['url']->setAssetRoot($this->app['url']->route('stancl.tenancy.asset', ['path' => '']));
            }
        }

        // Storage facade
        Storage::forgetDisk($this->app['config']['tenancy.filesystem.disks']);

        foreach ($this->app['config']['tenancy.filesystem.disks'] as $disk) {
            $originalRoot = $this->app['config']["filesystems.disks.{$disk}.root"];
            $this->originalPaths['disks'][$disk] = $originalRoot;

            $finalPrefix = str_replace(
                ['%storage_path%', '%tenant%'],
                [storage_path(), $tenant->table_prefix],
                $this->app['config']["tenancy.filesystem.root_override.{$disk}"] ?? '',
            );

            if (! $finalPrefix) {
                $finalPrefix = $originalRoot
                    ? rtrim($originalRoot, '/') . '/'. $suffix
                    : $suffix;
            }

            $this->app['config']["filesystems.disks.{$disk}.root"] = $finalPrefix;
        }
    }

    protected function ensureTenantStorageDirectories(): void
    {
        $paths = [
            storage_path(),
            storage_path('app'),
            storage_path('app/imports'),
            storage_path('app/livewire-tmp'),
            storage_path('app/public'),
            storage_path('debugbar'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/testing'),
            storage_path('framework/views'),
            storage_path('indexes'),
            storage_path('logs'),
        ];

        foreach ($paths as $path) {
            File::ensureDirectoryExists($path, 0755, true);
        }
    }
}
