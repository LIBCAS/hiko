<?php

namespace App\Services;

use App\Models\Identity;

class SearchIdentity
{
    public function __invoke(string $query, int $limit = 10)
    {
        return Identity::select('id', 'name', 'birth_year', 'death_year')
            ->whereIn('id', Identity::search($query)->keys()->toArray())
            ->take($limit)
            ->get()
            ->map(function ($identity) {
                return [
                    'id' => $identity->id,
                    'label' => "{$identity->name} {$identity->dates}",
                ];
            });
    }
}
