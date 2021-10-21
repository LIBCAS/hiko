<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Identity;
use Illuminate\Http\Request;

class AjaxIdentityController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = $request->query('search');

        if (empty($search)) {
            return [];
        }

        return Identity::where('name', 'like', '%' . $search . '%')
            ->select('id', 'name')
            ->take(15)
            ->get()
            ->toArray();
    }
}
