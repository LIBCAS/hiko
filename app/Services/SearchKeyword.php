<?php

namespace App\Services;

use App\Models\Keyword;

class SearchKeyword
{
    public function __invoke(string $query, int $limit = 10)
    {
        return Keyword::query()
            ->select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
            ->take($limit)
            ->get();
    }
}
