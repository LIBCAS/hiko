<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SimilarLocationsController extends Controller
{
    public function __invoke(Request $request)
    {
        if (!$request->has('search')) {
            return [];
        }

        $searchQuery = Location::search($request->query('search'));

        return Location::select('id', 'name')
            ->whereIn('id', $searchQuery->keys()->toArray())
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->name,
                ];
            })
            ->toArray();
    }
}
