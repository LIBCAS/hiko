<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Stancl\Tenancy\Facades\Tenancy;

class Media extends BaseMedia
{
    protected $connection = 'tenant';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->tenant) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__media';
        }
    }
}
