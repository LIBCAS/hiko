<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\KeywordCategory;
use Illuminate\Http\Request;

class KeywordCategoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = $request->query('search');

        if (empty($search)) {
            return [];
        }

        $categories = KeywordCategory::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ["%$search%"])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ["%$search%"])
            ->select('id', 'name')
            ->take(15)
            ->get();

        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => implode(' | ', array_values($category->getTranslations('name'))),
            ];
        })->toArray();
    }
}
