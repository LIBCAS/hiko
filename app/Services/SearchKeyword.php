<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\GlobalKeyword;
use Illuminate\Support\Collection;

class SearchKeyword
{
    public function __invoke(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if (empty($query)) {
            return collect();
        }

        $local = Keyword::select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
            ->take($limit)
            ->get()
            ->map(function ($kw) {
                return [
                    'id' => 'local-' . $kw->id,
                    'label' => implode(' | ', array_values($kw->getTranslations('name'))) . ' (L.)',
                ];
            });

        $global = GlobalKeyword::select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
            ->take($limit)
            ->get()
            ->map(function ($kw) {
                return [
                    'id' => 'global-' . $kw->id,
                    'label' => implode(' | ', array_values($kw->getTranslations('name'))) . ' (G.)',
                ];
            });

        return $local->merge($global);
    }
}
