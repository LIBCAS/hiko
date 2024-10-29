<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DuplicateDetectionService;
use Stancl\Tenancy\Events\TenantCreated;
use App\Listeners\MigrateTenants;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Bind the DuplicateDetectionService
        $this->app->bind(DuplicateDetectionService::class, function ($app, $parameters) {
            $prefixes = []; // Define prefixes if necessary
            return new DuplicateDetectionService($prefixes);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Load tenant migrations if tenancy is initialized
        if (tenancy()->initialized) {
            $this->loadMigrationsFrom(database_path('migrations/tenant'));
        }

        // Listen for tenant creation events to run migrations
        Event::listen(TenantCreated::class, MigrateTenants::class);
    }
}
