<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Services\SearchPlace;
use App\Services\SearchKeyword;
use App\Services\SearchIdentity;
use App\Http\Controllers\Controller;

class FacetsController extends Controller
{
    protected $models = [
        'identity' => 'searchIdentity',
        'place' => 'searchPlace',
        'keyword' => 'searchKeyword',
    ];

    public function __invoke(Request $request)
    {
        $model = $request->input('model');

        if (!array_key_exists($model, $this->models)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->has('query')) {
            return response()->json(['message' => 'Bad request'], 400);
        }

        return call_user_func(
            [$this, $this->models[$model]],
            $request->input('query'),
        );
    }

    protected function searchIdentity(string $query)
    {
        $search = new SearchIdentity;
        return $search($query);
    }

    protected function searchPlace(string $query)
    {
        $search = new SearchPlace;
        return $search($query);
    }

    protected function searchKeyword(string $query)
    {
        $search = new SearchKeyword;

        return $search($query)->map(function ($kw) {
            return [
                'id' => $kw->id,
                'label' => implode(' | ', array_values($kw->getTranslations('name'))),
            ];
        });
    }
}
