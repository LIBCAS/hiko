<?php

namespace App\Services;

use App\Models\Identity;

class SearchIdentity
{
    public function __invoke(array $filters = [], int $limit = 10)
    {
        return Identity::query()
            ->select('id', 'name', 'birth_year', 'death_year')
            ->when(isset($filters['name']), function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            })
            ->when(isset($filters['related_names']), function ($query) use ($filters) {
                $query->where('related_names', 'like', '%' . $filters['related_names'] . '%');
            })
            ->when(isset($filters['type']), function ($query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->when(isset($filters['profession']), function ($query) use ($filters) {
                $query->whereHas('professions', function ($professionQuery) use ($filters) {
                    $professionQuery->where('name', 'like', '%' . $filters['profession'] . '%');
                });
            })
            ->when(isset($filters['category']), function ($query) use ($filters) {
                $query->whereHas('profession_categories', function ($categoryQuery) use ($filters) {
                    $categoryQuery->where('name', 'like', '%' . $filters['category'] . '%');
                });
            })
            ->when(isset($filters['note']), function ($query) use ($filters) {
                $query->where('note', 'like', '%' . $filters['note'] . '%');
            })
            ->take($limit)
            ->get()
            ->map(function ($identity) {
                $birthYear = $identity->birth_year ? $identity->birth_year : '?';
                $deathYear = $identity->death_year ? $identity->death_year : '?';

                return [
                    'id' => $identity->id,
                    'label' => $identity->name ? "{$identity->name} ({$birthYear} - {$deathYear})" : 'No Name (Local)',
                ];
            });
    }
}
