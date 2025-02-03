<?php

namespace App\Providers;

use App\Models\TenantMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantInitialized;
use App\Services\LetterComparisonService;
use App\Services\GoogleDocumentAIService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Register Google Document AI service as a singleton
        $this->app->singleton(GoogleDocumentAIService::class, function ($app) {
            return new GoogleDocumentAIService($app->make('config')->get('google-document-ai'));
        });

        // Register LetterComparisonService as a singleton
        $this->app->singleton(LetterComparisonService::class, function ($app) {
            return new LetterComparisonService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->app->bind(Media::class, TenantMedia::class);
        
        // If you have tenant-specific migrations:
        $this->loadMigrationsFrom(database_path('migrations/tenant'));

        // Dynamically set the table for Media model based on the tenant
        Event::listen(TenantInitialized::class, function (TenantInitialized $event) {
            $tenantPrefix = $event->tenant->table_prefix;
            Media::getModel()->setTable("{$tenantPrefix}__media");
        });
    }
}
