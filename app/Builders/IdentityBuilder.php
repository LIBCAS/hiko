<?php

namespace App\Builders;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class IdentityBuilder extends Builder
{
    public function search($filters): IdentityBuilder
    {
        if (isset($filters['name']) && !empty($filters['name'])) {
            $this->where('name', 'LIKE', "%" . $filters['name'] . "%")
                ->orWhereRaw("LOWER(alternative_names) like ?", ["%" . Str::lower($filters['name']) . "%"]);
        }

        if (isset($filters['related_names']) && !empty($filters['related_names'])) {
            $this->where('related_names', 'LIKE', "%" . $filters['related_names'] . "%");
        }

        if (isset($filters['type']) && !empty($filters['type'])) {
            $this->where('type', '=', $filters['type']);
        }

        if (isset($filters['profession']) && !empty($filters['profession'])) {
            $this->whereHas('professions', function ($subquery) use ($filters) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($filters['profession']) . '%'])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($filters['profession']) . '%']);
            });
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $this->whereHas('profession_categories', function ($subquery) use ($filters) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($filters['category']) . '%'])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($filters['category']) . '%']);
            });
        }

        if (isset($filters['note']) && !empty($filters['note'])) {
            $this->where('note', 'LIKE', "%" . $filters['note'] . "%");
        }

        return $this;
    }
}
