<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Place;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SimilarPlacesController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->has('search')) {
            return [];
        }

        $searchQuery = Place::search($request->query('search'));

        return Place::select('id', 'name', 'division')
            ->whereIn('id', $searchQuery->keys()->toArray())
            ->get()
            ->map(function ($place) {
                return [
                    'id' => $place->id,
                    'label' => "{$place->name} ({$place->division}-{$place->country})",
                ];
            })
            ->toArray();
    }
}
