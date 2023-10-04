<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SimilarLocationsController extends Controller
{
    public function __invoke(Request $request): array
    {
        $query = trim($request->query('search'));

        if (!$request->has('search') || empty($query)) {
            return [];
        }

        return Location::select('id', 'name')
            ->where('name', 'like', '%' . $query . '%')
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
