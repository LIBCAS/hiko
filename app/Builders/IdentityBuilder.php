<?php

namespace App\Builders;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\GlobalProfession;
use App\Models\GlobalProfessionCategory;

class IdentityBuilder extends Builder
{
    public function search($filters): IdentityBuilder
    {
        if (isset($filters['name']) && !empty($filters['name'])) {
            $this->where('name', 'LIKE', "%" . $filters['name'] . "%")
                ->orWhereRaw("LOWER(alternative_names) LIKE ?", ["%" . Str::lower($filters['name']) . "%"]);
        }

        if (isset($filters['related_names']) && !empty($filters['related_names'])) {
            $this->where('related_names', 'LIKE', "%" . $filters['related_names'] . "%");
        }

        if (isset($filters['type']) && !empty($filters['type'])) {
            $this->where('type', '=', $filters['type']);
        }

        if (isset($filters['profession']) && !empty($filters['profession'])) {
            $this->where(function ($query) use ($filters) {
                // Search local professions if in a tenant context
                $query->whereHas('professions', function ($subquery) use ($filters) {
                    $subquery
                        ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) LIKE ?", ['%' . Str::lower($filters['profession']) . '%'])
                        ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) LIKE ?", ['%' . Str::lower($filters['profession']) . '%']);
                });

                // Search global professions in central context
                Tenancy::central(function () use ($query, $filters) {
                    $query->orWhereHas('global_professions', function ($subquery) use ($filters) {
                        $subquery
                            ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) LIKE ?", ['%' . Str::lower($filters['profession']) . '%'])
                            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) LIKE ?", ['%' . Str::lower($filters['profession']) . '%']);
                    });
                });
            });
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $this->where(function ($query) use ($filters) {
                // Search local profession categories if in a tenant context
                $query->whereHas('profession_categories', function ($subquery) use ($filters) {
                    $subquery
                        ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) LIKE ?", ['%' . Str::lower($filters['category']) . '%'])
                        ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) LIKE ?", ['%' . Str::lower($filters['category']) . '%']);
                });

                // Search global profession categories in central context
                Tenancy::central(function () use ($query, $filters) {
                    $query->orWhereHas('global_profession_categories', function ($subquery) use ($filters) {
                        $subquery
                            ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) LIKE ?", ['%' . Str::lower($filters['category']) . '%'])
                            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) LIKE ?", ['%' . Str::lower($filters['category']) . '%']);
                    });
                });
            });
        }

        if (isset($filters['note']) && !empty($filters['note'])) {
            $this->where('note', 'LIKE', "%" . $filters['note'] . "%");
        }

        return $this;
    }

    public function withLocalAndGlobalProfessions()
    {
        $this->with(['professions' => function ($query) {
            $query->selectRaw("id, JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) as name, 'Local' as scope");
        }]);

        Tenancy::central(function () {
            $globalProfessions = GlobalProfession::selectRaw("id, JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) as name, 'Global' as scope")->get();
            $this->getModel()->globalProfessions = $globalProfessions;
        });

        return $this;
    }
}
