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
        $query = $request->input('query');

        if (!array_key_exists($model, $this->models)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->has('query')) {
            return response()->json(['message' => 'Bad request'], 400);
        }

        return match ($model) {
            'identity' => $this->searchIdentity(['name' => $query]),
            'place'    => $this->searchPlace($query),
            'keyword'  => $this->searchKeyword($query),
        };
    }

    protected function searchIdentity(array $filters)
    {
        return (new SearchIdentity())($filters);
    }

    protected function searchPlace(string $query)
    {
        return (new SearchPlace())($query);
    }

    protected function searchKeyword(string $query)
    {
        return (new SearchKeyword())($query);
    }
}
