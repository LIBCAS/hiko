<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class LocationBuilder extends Builder
{
    public function search($filters): LocationBuilder
    {
        if (isset($filters['name']) && !empty($filters['name'])) {
            $this->where('name', 'LIKE', "%" . $filters['name'] . "%");
        }

        if (isset($filters['type']) && !empty($filters['type'])) {
            $this->where('type', '=', $filters['type']);
        }

        return $this;
    }
}
