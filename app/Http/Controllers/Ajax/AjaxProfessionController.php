<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Profession;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AjaxProfessionController extends Controller
{
    public function __invoke(Request $request)
    {
        $search = $request->query('search');

        if (empty($search)) {
            return [];
        }

        $professions = Profession::whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ["%$search%"])
            ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ["%$search%"])
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
