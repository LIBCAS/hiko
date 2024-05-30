<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\SearchPlace;
use App\Services\SearchKeyword;
use App\Services\SearchIdentity;
use App\Http\Controllers\Controller;

class FacetsController extends Controller
{
    protected array $models = [
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
        return (new SearchIdentity())($query);
    }

    protected function searchPlace(string $query)
    {
        return (new SearchPlace())($query);
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
