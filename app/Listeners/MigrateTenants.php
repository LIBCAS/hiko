<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MigrateTenants implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TenantCreated $event)
    {
        // Run tenant migrations when a new tenant is created
        \Artisan::call('tenants:migrate', [
            '--tenants' => [$event->tenant->id],
        ]);
    }
}
