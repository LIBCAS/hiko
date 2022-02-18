<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Profession;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AjaxProfessionController extends Controller
{
    public function __invoke(Request $request)
    {
        return empty($request->query('search'))
            ? []
            : Profession::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->select('id', 'name')
            ->take(10)
            ->get()
            ->map(function ($profession) {
                return [
                    'id' => $profession->id,
                    'value' => $profession->id,
                    'label' => $profession->getTranslation('name', config('hiko.metadata_default_locale')),
                ];
            })
            ->toArray();
    }
}
