<?php

namespace App\Traits;

use Stancl\Tenancy\Tenancy; // Use this instead of Stancl\Tenancy\Contracts\Tenancy

trait TenantAware
{
    protected function getTenantPrefix()
    {
        return $this->getTenancy()->tenant->table_prefix;
    }

    protected function getTenancy(): Tenancy
    {
        return app(Tenancy::class);
    }
}
