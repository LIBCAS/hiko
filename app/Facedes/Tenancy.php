<?php

namespace App\Facades;

use Stancl\Tenancy\Tenancy as BaseTenancy;

class Tenancy extends BaseTenancy
{
    public static function checkTenancyInitialized()
    {
        return tenancy()->initialized;
    }

    public static function getTenantPrefix()
    {
        return tenancy()->tenant->table_prefix;
    }
}
