<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProfessionCategory;
use App\Http\Controllers\Controller;

class AjaxProfessionCategoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = $request->query('search');

        if (empty($search)) {
            return [];
        }

        $professions = ProfessionCategory::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($search) . '%'])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($search) . '%'])
            ->select('id', 'name')
            ->take(15)
            ->get();

        return $professions->map(function ($profession) {
            return [
                'id' => $profession->id,
                'name' => implode(' | ', array_values($profession->getTranslations('name'))),
            ];
        })->toArray();
    }
}
