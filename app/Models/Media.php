<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->setTable("{$tenantPrefix}__media");
        }
    }
}
