<?php

namespace App\Services;

use App\Models\Keyword;

class SearchKeyword
{
    public function __invoke(string $query, int $limit = 10)
    {
        $query = trim($query);

        if (empty($query)) {
            return [];
        }

        return Keyword::select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
            ->take($limit)
            ->get();
    }
}
