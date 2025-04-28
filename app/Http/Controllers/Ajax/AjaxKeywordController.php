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
        $searchTerm = mb_strtolower($request->query('search'));
        if (empty($searchTerm)) {
            return [];
        }

        $locale = config('app.locale');

        // Force into plain Collection
        $tenantKeywords = collect(
            Keyword::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$locale}\"'))) LIKE ?", ["%{$searchTerm}%"])
                ->get()
                ->map(fn($keyword) => [
                    'id' => 'local-' . $keyword->id,
                    'value' => 'local-' . $keyword->id,
                    'label' => $keyword->getTranslation('name', $locale) . ' (' . __('hiko.local') . ')',
                    'type' => __('hiko.local')
                ])
                ->values()
                ->toArray()
        );

        $globalKeywords = collect(
            GlobalKeyword::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$locale}\"'))) LIKE ?", ["%{$searchTerm}%"])
                ->get()
                ->map(fn($keyword) => [
                    'id' => 'global-' . $keyword->id,
                    'value' => 'global-' . $keyword->id,
                    'label' => $keyword->getTranslation('name', $locale) . ' (' . __('hiko.global') . ')',
                    'type' => __('hiko.global')
                ])
                ->values()
                ->toArray()
        );

        return $tenantKeywords
            ->merge($globalKeywords)
            ->sortBy('label')
            ->values()
            ->toArray();
    }
}
