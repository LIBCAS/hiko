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
                    'name' => $keyword->getTranslation('name', $locale),
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
                    'name' => $keyword->getTranslation('name', $locale),
                    'label' => $keyword->getTranslation('name', $locale) . ' (' . __('hiko.global') . ')',
                    'type' => __('hiko.global')
                ])
                ->values()
                ->toArray()
        );

        $needle = trim((string) $request->query('search', ''));
        $needleNorm = removeAccents(mb_strtolower($needle, 'UTF-8'));

        return $tenantKeywords
            ->merge($globalKeywords)
            ->sort(function (array $a, array $b) use ($needleNorm) {
                $aName = removeAccents(mb_strtolower($a['name'] ?? '', 'UTF-8'));
                $bName = removeAccents(mb_strtolower($b['name'] ?? '', 'UTF-8'));

                $aExact = ($aName === $needleNorm) ? 0 : 1; // 0 sorts first
                $bExact = ($bName === $needleNorm) ? 0 : 1;

                // exact matches first
                if ($aExact !== $bExact) {
                    return $aExact <=> $bExact;
                }

                // then alphabetical by displayed label
                return strcasecmp($a['label'] ?? '', $b['label'] ?? '');
            })
            ->values()
            ->toArray();
    }
}
