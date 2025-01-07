<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\GlobalKeyword;
use App\Models\Keyword;

class AjaxKeywordController extends Controller
{
    public function __invoke(Request $request): array
    {
        $searchTerm = $request->query('search');
        if (empty($searchTerm)) {
            return [];
        }

        $tenantKeywords = Keyword::where('name', 'like', "%{$searchTerm}%")->get();
        $globalKeywords = GlobalKeyword::where('name', 'like', "%{$searchTerm}%")->get();

        $keywords = $tenantKeywords->merge($globalKeywords);

        return $keywords->map(fn($keyword) => [
            'id' => $keyword->id,
            'value' => $keyword->id,
            'label' => $keyword->getTranslation('name', config('app.locale')),
        ])->toArray();
    }
}
