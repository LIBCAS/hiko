<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Services\SearchPlace;
use App\Http\Controllers\Controller;

class SimilarPlacesController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (!$request->has('search')) {
            return [];
        }

        $search = new SearchPlace;

        return $search($request->input('search'))->toArray();
    }
}
