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
        $search = $request->query('search');

        if (empty($search)) {
            return [];
        }

        $keywords = Keyword::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($search) . '%'])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($search) . '%'])
            ->select('id', 'name')
            ->take(15)
            ->get();

        return $keywords->map(function ($keyword) {
            return [
                'id' => $keyword->id,
                'name' => implode(' | ', array_values($keyword->getTranslations('name'))),
            ];
        })->toArray();
    }
}
