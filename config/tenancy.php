<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use App\Models\Tenant;

return [
    'tenant_model' => Tenant::class,
    'id_generator' => null,

    'domain_model' => Domain::class,

    'central_domains' => [
        '127.0.0.1',
        'localhost',
    ],

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class, // Enabled
        App\Tenancy\ConfigFilesystemTenancyBootstrapper::class,
        App\Tenancy\ConfigTenancyBootstrapper::class,    
    ],    

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'central'),
    
        'template_tenant_connection' => null,
    
        'prefix' => '{prefix}__', // Dynamic prefix pattern
        'suffix' => '',
    
        'managers' => [
            'sqlite' => Stancl\Tenancy\DatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\DatabaseManagers\PrefixDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\DatabaseManagers\PostgreSQLDatabaseManager::class,
        ], 
    ],

    'cache' => [
        'tag_base' => 'tenant', // This tag_base, followed by the tenant_id, will form a tag that will be applied on each cache call.
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3',
        ],

        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'suffix_storage_path' => true,

        'asset_helper_tenancy' => false,
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            // 'default',
        ],
    ],

    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        // Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
        // Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
        // Stancl\Tenancy\Features\ViteBundler::class,
    ],

    /**
     * Should tenancy routes be registered.
     *
     * Tenancy routes include tenant asset routes. By default, this route is
     * enabled. But it may be useful to disable them if you use external
     * storage (e.g. S3 / Dropbox) or have a custom asset controller.
     */
    'routes' => true,

    /**
     * Parameters used by the tenants:migrate command.
     */
    'migration_parameters' => [
        '--force' => true, // This needs to be true to run migrations in production.
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    /**
     * Parameters used by the tenants:seed command.
     */
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder', // root seeder class
        // '--force' => true,
    ],
];
