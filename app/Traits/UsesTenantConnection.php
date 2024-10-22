<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait UsesTenantConnection
{
    /**
     * Initialize the tenant-specific table name if tenancy is initialized.
     *
     * @return void
     */
    public function initializeUsesTenantTable()
    {
        if (tenancy()->initialized) {
            $tenantPrefix = $this->getTenantPrefix();

            // Set the table with tenant prefix
            $tableName = $tenantPrefix . '__' . $this->getTable();

            Log::info('Using tenant-specific table: ' . $tableName);

            $this->setTable($tableName);
        } else {
            Log::warning('Tenancy is not initialized, using default table.');
        }
    }

    /**
     * Get the tenant-specific table prefix.
     *
     * @return string
     */
    public function getTenantPrefix()
    {
        $prefix = tenancy()->tenant->table_prefix ?? '';

        Log::info('Tenant prefix fetched: ' . $prefix);

        // Ensure no trailing underscore
        return rtrim($prefix, '_');
    }
}
