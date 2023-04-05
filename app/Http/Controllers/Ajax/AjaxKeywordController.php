<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Services\SearchKeyword;
use App\Http\Controllers\Controller;

class AjaxKeywordController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (empty($request->query('search'))) {
            return [];
        }

        $search = new SearchKeyword;

        return $search($request->query('search'))
            ->map(function ($kw) {
                return [
                    'id' => $kw->id,
                    'value' => $kw->id,
                    'label' => $kw->getTranslation('name', config('hiko.metadata_default_locale')),
                ];
            })
            ->toArray();
    }
}
