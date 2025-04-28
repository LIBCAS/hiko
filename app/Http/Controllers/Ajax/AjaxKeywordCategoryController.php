<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\KeywordCategory;
use App\Http\Controllers\Controller;

class AjaxKeywordCategoryController extends Controller
{
    public function __invoke(Request $request): array
    {
        $query = Str::lower(trim($request->query('search', '')));

        if (empty($query)) {
            $categories = KeywordCategory::latest()
                ->take(25)
                ->get();
        } else {
            $categories = KeywordCategory::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ["%{$query}%"])
                ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ["%{$query}%"])
                ->orderByRaw("LOWER(JSON_EXTRACT(name, '$.cs')) ASC")
                ->take(25)
                ->get();
        }

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'value' => $category->id,
                'label' => $category->getTranslation('name', config('hiko.metadata_default_locale')),
            ];
        })->toArray();
    }
}
