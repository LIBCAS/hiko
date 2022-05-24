<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Services\SearchIdentity;
use App\Http\Controllers\Controller;

class AjaxIdentityController extends Controller
{
    public function __invoke(Request $request)
    {
        if (empty($request->query('search'))) {
            return [];
        }

        $search = new SearchIdentity;

        return $search($request->input('search'))
            ->map(function ($identity) {
                return [
                    'id' => $identity['id'],
                    'value' => $identity['id'],
                    'label' => $identity['label'],
                ];
            })
            ->toArray();
    }
}
