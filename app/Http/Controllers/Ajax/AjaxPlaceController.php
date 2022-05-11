<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;

class AjaxPlaceController extends Controller
{
    public function __invoke(Request $request)
    {
        return empty($request->query('search'))
            ? []
            : Place::where('name', 'like', '%' . $request->query('search') . '%')
            ->select('id', 'name', 'country', 'division')
            ->take(15)
            ->get()
            ->map(function ($place) {
                return [
                    'id' => $place->id,
                    'value' => $place->id,
                    'label' => "{$place->name} ({$place->division}-{$place->country})",
                ];
            })
            ->toArray();
    }
}
