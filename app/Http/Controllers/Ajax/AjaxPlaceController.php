<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;

class AjaxPlaceController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = $request->query('search');

        if (empty($search)) {
            return [];
        }

        return Place::where('name', 'like', '%' . $search . '%')
            ->select('id', 'name')
            ->take(15)
            ->get()
            ->toArray();
    }
}
