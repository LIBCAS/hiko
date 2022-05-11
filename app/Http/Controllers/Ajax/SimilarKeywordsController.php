<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Keyword;
use Illuminate\Http\Request;

class SimilarKeywordsController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->has('search')) {
            return [];
        }

        $searchQuery = Keyword::search($request->query('search'));

        return Keyword::select('id', 'name')
            ->whereIn('id', $searchQuery->keys()->toArray())
            ->get()
            ->map(function ($kw) {
                return [
                    'id' => $kw->id,
                    'label' => implode(' | ', array_values($kw->getTranslations('name')))
                ];
            })
            ->toArray();
    }
}
