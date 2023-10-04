<?php

namespace App\Services;

use App\Models\Identity;

class SearchIdentity
{
    public function __invoke(string $query, int $limit = 10)
    {
        $query = trim($query);

        if (empty($query)) {
            return [];
        }

        return Identity::query()
            ->select('id', 'name', 'birth_year', 'death_year')
            ->where('name', 'like', '%' . $query . '%')
            ->orWhere('surname', 'like', '%' . $query . '%')
            ->orWhere('forename', 'like', '%' . $query . '%')
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
