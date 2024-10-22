<?php

namespace App\Traits;

trait UsesTenantConnection
{
    // Initialize the tenant-specific table if tenancy is initialized
    public function initializeUsesTenantTable()
    {
        if (tenancy()->initialized) {
            $tableName = $this->getTenantPrefix() . '__' . $this->getTable();
            \Log::info('Using tenant-specific table: ' . $tableName);
            $this->setTable($tableName);
        }
    }    

    // Get tenant-specific prefix from tenancy instance
    public function getTenantPrefix()
    {
        $prefix = tenancy()->tenant->table_prefix ?? '';
        
        // Ensure there's no accidental trailing underscore in the table prefix
        return rtrim($prefix, '_');
    }    
}
