<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class Role extends Model
{
    use Sushi;

    public function getRows()
    {
        return [
            [
                'id' => 1,
                'label' => 'admin',
                'abilities' =>
                'manage-metadata,view-metadata,manage-users',
            ],
            [
                'id' => 2,
                'label' => 'editor',
                'abilities' => 'manage-metadata,view-metadata',
            ],
            [
                'id' => 3,
                'label' => 'guest',
                'abilities' => 'view-metadata',
            ],
            [
                'id' => 4,
                'label' => 'developer',
                'abilities' => 'manage-metadata,view-metadata,manage-users,debug',
            ],
        ];
    }
}
