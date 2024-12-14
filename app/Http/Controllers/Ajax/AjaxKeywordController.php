<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SearchKeyword;

class AjaxKeywordController extends Controller
{
    public function __invoke(Request $request): array
    {
        $searchTerm = $request->query('search');
        if (empty($searchTerm)) {
            return [];
        }

        $searchService = new SearchKeyword;

        return $searchService($searchTerm)
            ->map(function ($keyword) {
                return [
                    'id' => $keyword->id,
                    'value' => $keyword->id,
                    'label' => $keyword->getTranslation('name', session('locale', config('hiko.metadata_default_locale'))),
                ];
            })
            ->toArray();
    }
}
