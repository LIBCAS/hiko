<?php

namespace App\Traits;

use Stancl\Tenancy\Tenancy;
use Illuminate\Support\Facades\Log;

trait TenantAware
{
    /**
     * Get the tenant's table prefix.
     *
     * @return string
     */
    protected function getTenantPrefix()
    {
        $tenancy = $this->getTenancy();
        $prefix = $tenancy->tenant->table_prefix ?? '';

        Log::info('Tenant prefix fetched: ' . $prefix);

        // Ensure there's no accidental trailing underscore
        return rtrim($prefix, '_');
    }

    /**
     * Retrieve the Tenancy instance.
     *
     * @return Tenancy
     */
    protected function getTenancy(): Tenancy
    {
        $tenancy = app(Tenancy::class);

        if (!$tenancy->initialized) {
            Log::warning('Tenancy is not initialized.');
        }

        return $tenancy;
    }
}
