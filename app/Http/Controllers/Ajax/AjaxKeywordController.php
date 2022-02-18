<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Keyword;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AjaxKeywordController extends Controller
{
    public function __invoke(Request $request)
    {
        return empty($request->query('search'))
            ? []
            : Keyword::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->select('id', 'name')
            ->take(15)
            ->get()
            ->map(function ($keyword) {
                return [
                    'id' => $keyword->id,
                    'value' => $keyword->id,
                    'label' => $keyword->getTranslation('name', config('hiko.metadata_default_locale')),
                ];
            })
            ->toArray();
    }
}
