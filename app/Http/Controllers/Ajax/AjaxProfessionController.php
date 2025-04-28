<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Profession;
use App\Models\GlobalProfession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\DB;

class AjaxProfessionController extends Controller
{
    public function __invoke(Request $request): array
    {
        $searchTerm = mb_strtolower($request->query('search'));
        if (empty($searchTerm)) {
            return [];
        }

        $locale = config('app.locale');

        // Force into plain Collection
        $tenantProfessions = collect(
            Profession::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$locale}\"'))) LIKE ?", ["%{$searchTerm}%"])
                ->get()
                ->map(fn($prof) => [
                    'id' => 'local-' . $prof->id,
                    'value' => 'local-' . $prof->id,
                    'label' => $prof->getTranslation('name', $locale) . ' (' . __('hiko.local') . ')',
                    'type' => __('hiko.local')
                ])
                ->values()
                ->toArray()
        );

        $globalProfessions = collect(
            GlobalProfession::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$locale}\"'))) LIKE ?", ["%{$searchTerm}%"])
                ->get()
                ->map(fn($prof) => [
                    'id' => 'global-' . $prof->id,
                    'value' => 'global-' . $prof->id,
                    'label' => $prof->getTranslation('name', $locale) . ' (' . __('hiko.global') . ')',
                    'type' => __('hiko.global')
                ])
                ->values()
                ->toArray()
        );

        return $tenantProfessions
            ->merge($globalProfessions)
            ->sortBy('label')
            ->values()
            ->toArray();
    }
}
