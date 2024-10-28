<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimilarItemsController extends Controller
{
    public function __invoke(Request $request): array
    {
        $query = trim($request->query('search'));

        if (!$request->has('search') || !$request->has('model') || empty($query)) {
            return [];
        }

        $model = app('App\Models\\' . $request->query('model'));

        return $model::select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
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
