<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;

class UserBuilder extends Builder
{
    public function search($filters)
    {
        if (isset($filters['name']) && !empty($filters['name'])) {
            $this->where('name', 'LIKE', "%" . $filters['name'] . "%");
        }

        if (isset($filters['role']) && !empty($filters['role'])) {
            $this->where('role', '=', $filters['role']);
        }

        if (isset($filters['status'])) {
            if ($filters['status'] === '1') {
                $this->whereNull('deactivated_at');
            } elseif ($filters['status'] === '0') {
                $this->whereNotNull('deactivated_at');
            }
        }

        return $this;
    }
}
