<?php

namespace App\Builders;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class ProfessionBuilder extends Builder
{
    public function search($filters): ProfessionBuilder
    {
        if (isset($filters['cs']) && !empty($filters['cs'])) {
            $this->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($filters['cs']) . '%']);
        }

        if (isset($filters['en']) && !empty($filters['en'])) {
            $this->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($filters['en']) . '%']);
        }

        if (isset($filters['category']) && !empty($filters['category'])) {
            $this->whereHas('profession_category', function ($subquery) use ($filters) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($filters['category']) . '%'])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($filters['category']) . '%']);
            });
        }

        return $this;
    }
}
