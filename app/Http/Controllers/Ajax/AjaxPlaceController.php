<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Services\SearchPlace;
use App\Http\Controllers\Controller;

class AjaxPlaceController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (empty($request->query('search'))) {
            return [];
        }

        $search = new SearchPlace;

        return $search($request->input('search'))
            ->map(function ($place) {
                return [
                    'id' => $place['id'],
                    'value' => $place['id'],
                    'label' => $place['label'],
                ];
            })
            ->toArray();
    }
}
