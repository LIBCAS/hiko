<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Identity;
use Illuminate\Http\Request;

class AjaxIdentityController extends Controller
{
    public function __invoke(Request $request)
    {
        return empty($request->query('search'))
            ? []
            : Identity::where('name', 'like', '%' . $request->query('search') . '%')
            ->select('id', 'name')
            ->take(15)
            ->get()
            ->map(function ($identity) {
                return [
                    'id' => $identity->id,
                    'value' => $identity->id,
                    'label' => $identity->name,
                ];
            })
            ->toArray();
    }
}
