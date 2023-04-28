<?php

namespace App\Services;

use App\Models\Place;

class SearchPlace
{
    public function __invoke(string $query, int $limit = 10)
    {
        return Place::select('id', 'name', 'division', 'country')
            ->whereIn('id', Place::search($query)->keys()->toArray())
            ->take($limit)
            ->get()
            ->map(function ($place) {
                $label = $place->division
                    ? "{$place->division}-{$place->country}"
                    : $place->country;

                return [
                    'id' => $place->id,
                    'label' => "{$place->name} ({$label})",
                ];
            });
    }
}
