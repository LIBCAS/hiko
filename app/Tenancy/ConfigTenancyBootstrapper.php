<?php

namespace App\Tenancy;

use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;
use Illuminate\Support\Facades\Log;

class ConfigTenancyBootstrapper implements TenancyBootstrapper
{
    private $subdomain;

    public function bootstrap(Tenant $tenant)
    {
        config([
            'app.name' => $tenant->name,
            'database.connections.tenant.prefix' => $tenant->id . '__',
            'logging.default' => 'tenant',
            'logging.channels.tenant.path' => storage_path() . '/../logs/' . $tenant->id . '/laravel.log',  // Here the stancl/tenancy package automatically sets the value of `storage_path()` as `'/storage/tenant' . $tenant->id`
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
            'hiko.geonames_username' => config('hiko.geonames_username'),
            'hiko.main_character' => config('hiko.main_character'),
            'hiko.metadata_default_locale' => config('hiko.metadata_default_locale'),
            'hiko.version' => config('hiko.version'),
            'hiko.show_watermark' => config('hiko.show_watermark'),
            'hiko.public_url' => config('hiko.public_url'),
        ]);
    }
}
