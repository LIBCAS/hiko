<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    protected $connection = 'tenant';

    public function getTable()
    {
        if (tenancy()->initialized) {
            return tenancy()->tenant->table_prefix . '__media';
        }

        // Если тенант не инициализирован, можно вернуть глобальную таблицу или бросить исключение
        return 'global_media';
    }
}
