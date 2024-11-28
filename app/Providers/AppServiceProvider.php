<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DuplicateDetectionService;
use App\Services\GoogleDocumentAIService;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load migrations from specific paths
        $this->loadMigrationsFrom(database_path('migrations/tenant'));

        // Optionally, listen for tenant creation to run migrations
        \Event::listen(\Stancl\Tenancy\Events\TenantCreated::class, MigrateTenants::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(GoogleDocumentAIService::class, function ($app) {
            return new GoogleDocumentAIService();
        });
        $this->app->bind(DuplicateDetectionService::class, function ($app, $parameters) {
            $prefixes = [];
            return new DuplicateDetectionService($prefixes);
        });
    }
}
