<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimilarItemsController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->has('search') || !$request->has('model')) {
            return [];
        }

        $model = app('App\Models\\' . $request->query('model'));

        $searchQuery = $model::search($request->query('search'));

        return $model::select('id', 'name')
            ->whereIn('id', $searchQuery->keys()->toArray())
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => implode(' | ', array_values($item->getTranslations('name'))),
                ];
            })
            ->toArray();
    }
}
