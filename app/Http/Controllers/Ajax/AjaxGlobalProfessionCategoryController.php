<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\GlobalProfessionCategory;

class AjaxGlobalProfessionCategoryController extends Controller
{
    public function __invoke(Request $request): array
    {
        return empty($request->query('search'))
            ? []
            : GlobalProfessionCategory::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($request->query('search')) . '%'])
            ->select('id', 'name')
            ->take(10)
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
