<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\KeywordCategory;
use App\Http\Controllers\Controller;

class KeywordCategoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = $request->query('search');

        if (empty($search)) {
            return [];
        }

        $categories = KeywordCategory::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($search) . '%'])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($search) . '%'])
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
