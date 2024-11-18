<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantModel extends Model
{
    use HasFactory;

    /**
     * Boot the model and apply tenant prefix and global scopes.
     */
    protected static function boot()
    {
        parent::boot();

        // Apply tenant table prefix if tenancy is initialized
        if (tenant()) {
            $prefix = tenant()->table_prefix;
            $baseTable = static::getBaseTableName();
            static::setTable("{$prefix}__{$baseTable}");

            // Apply a global scope to ensure all queries are tenant-specific
            static::addGlobalScope('tenant', function (Builder $builder) {
                // Assuming each tenant table has a 'tenant_id' column
                // Uncomment the following line if 'tenant_id' exists
                $builder->where('tenant_id', tenant()->id);
            });
        }
    }

    /**
     * Get the base table name without prefix.
     *
     * @return string
     */
    protected static function getBaseTableName(): string
    {
        return static::$baseTable ?? (new static)->getTable();
    }
}