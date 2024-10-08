<?php

namespace App\Tenancy;

use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class ConfigTenancyBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        config([
            'app.name' => $tenant->name,
            'database.default' => 'tenant',
            'database.connections.tenant.prefix' => $tenant->table_prefix . '__',  // Dynamic table prefix for tenant
            'logging.default' => 'tenant',  // Tenant-specific logging channel
            'logging.channels.tenant.path' => storage_path() . '/logs/tenant-' . $tenant->table_prefix . '.log',  // Log file per tenant
            'scout.prefix' => $tenant->table_prefix . '__',  // Prefix for scout search
            'hiko.geonames_username' => $tenant->geonames_username,
            'hiko.main_character' => $tenant->main_character,
            'hiko.metadata_default_locale' => $tenant->metadata_default_locale,
            'hiko.version' => $tenant->version,
            'hiko.show_watermark' => $tenant->show_watermark,
            'hiko.public_url' => $tenant->public_url,
        ]);
    }

    public function revert()
    {
        config([
            'app.name' => config('app.name'),
            'database.connections.tenant.prefix' => config('database.connections.tenant.prefix'),
            'logging.default' => config('logging.default'),
            'logging.channels.tenant.path' => config('logging.channels.tenant.path'),
            'scout.prefix' => config('scout.prefix'),
            'hiko.geonames_username' => config('hiko.geonames_username'),
            'hiko.main_character' => config('hiko.main_character'),
            'hiko.metadata_default_locale' => config('hiko.metadata_default_locale'),
            'hiko.version' => config('hiko.version'),
            'hiko.show_watermark' => config('hiko.show_watermark'),
            'hiko.public_url' => config('hiko.public_url'),
        ]);
    }
}
