<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use App\Policies\ProfessionPolicy;
use App\Policies\ProfessionCategoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        Profession::class => ProfessionPolicy::class,
        ProfessionCategory::class => ProfessionCategoryPolicy::class,
        // Add other model-policy mappings here
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();

        // Additional boot logic if necessary
    }
}
