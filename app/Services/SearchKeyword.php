<?php

namespace App\Services;

use App\Models\Keyword;

class SearchKeyword
{
    public function __invoke(string $query, int $limit = 10)
    {
        return Keyword::select('id', 'name')
            ->whereIn('id', Keyword::search($query)->keys()->toArray())
            ->take($limit)
            ->get();
    }
}
