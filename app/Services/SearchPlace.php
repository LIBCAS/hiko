<?php

namespace App\Services;

use App\Models\Place;

class SearchPlace
{
    public function __invoke(string $query, int $limit = 10)
    {
        $query = trim($query);

        if (empty($query)) {
            return [];
        }

        return Place::query()
        ->select('id', 'name', 'division', 'country')
        ->where(function ($queryBuilder) use ($query) {
            $queryBuilder->where('name', 'like', '%' . $query . '%')
                ->orWhere('country', 'like', '%' . $query . '%')
                ->orWhere('division', 'like', '%' . $query . '%')
                ->orWhereRaw("JSON_SEARCH(alternative_names, 'one', ?)", ["%{$query}%"]);
        })
        ->take($limit)
        ->get()
        ->map(function ($place) {
            $label = $place->division
                ? "{$place->division} - {$place->country}"
                : $place->country;
    
            return [
                'id' => $place->id,
                'label' => "{$place->name} ({$label})",
            ];
        });    
    }
}
