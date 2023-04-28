<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class PlaceBuilder extends Builder
{
    public function search($filters): PlaceBuilder
    {
        if (isset($filters['name']) && !empty($filters['name'])) {
            $this->where('name', 'LIKE', "%" . $filters['name'] . "%");
        }

        if (isset($filters['country']) && !empty($filters['country'])) {
            $this->where('country', 'LIKE', "%" . $filters['country'] . "%");
        }

        return $this;
    }
}
