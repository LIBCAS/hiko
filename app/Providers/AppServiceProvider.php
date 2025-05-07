<?php

namespace App\Providers;

use App\Models\TenantMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Stancl\Tenancy\Events\TenantInitialized;
use App\Services\LetterComparisonService;
use App\Services\GoogleDocumentAIService;
use App\Auth\TenantDatabaseTokenRepository;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Hash;

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

        $this->app->singleton('tenant.password.broker', function ($app) {
            $config = $app['config']['auth.passwords.users'];
            $key = $app['config']['app.key'];

            if (str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            return new TenantDatabaseTokenRepository(
                $app['db']->connection('tenant'),
                $app['hash'],
                'password_resets', // dummy
                $key,
                $config['expire'] * 60,
                $config['throttle'] ?? 0
            );
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

        // Register the password broker for tenant-specific password resets
        $this->app->extend('auth.password', function ($service, $app) {
            $config = $app['config']['auth.passwords.users'];

            $key = $app['config']['app.key'];
            if (str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            $repository = new TenantDatabaseTokenRepository(
                $app['db']->connection('tenant'),
                Hash::driver(),
                'password_resets',
                $key,
                ($config['expire'] ?? 60) * 60,
                $config['throttle'] ?? 0,
            );

            return new PasswordBroker(
                $repository,
                $app['auth']->createUserProvider($config['provider']),
                $app['events']
            );
        });
    }
}
