<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\GlobalKeywordCategory;

class AjaxGlobalKeywordCategoryController extends Controller
{
    public function __invoke(Request $request): array
    {
        return empty($request->query('search'))
            ? []
            : GlobalKeywordCategory::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->select('id', 'name')
            ->take(25)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'value' => $category->id,
                    'label' => $category->getTranslation('name', config('hiko.metadata_default_locale')),
                ];
            })
            ->toArray();
    }
}
