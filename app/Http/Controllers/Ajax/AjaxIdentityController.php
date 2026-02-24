<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Services\SearchIdentity;
use App\Http\Controllers\Controller;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\GlobalIdentity;
use App\Models\GlobalProfession;

class AjaxIdentityController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (empty($request->query('search'))) {
            return [];
        }

        $searchFilters = [
            'name' => $request->input('search')
        ];

        if ($request->has('allTypes') && !$request->boolean('allTypes')) {
            $searchFilters['type'] = $request->input('type', 'person');
        }

        // Search Local Identities
        $search = new SearchIdentity;
        $localResults = $search($searchFilters)
            ->map(function ($identity) {
                return [
                    'id' => 'local-' . $identity['id'],
                    'value' => 'local-' . $identity['id'],
                    'label' => $identity['label'] . ' (' . __('hiko.local') . ')',
                ];
            });

        // Search Global Identities (if explicitly requesting professions, skip this)
        $globalResults = collect();

        if ($request->input('type') !== 'profession') {
            $globalQuery = GlobalIdentity::query()
                ->select('id', 'name', 'birth_year', 'death_year');

            if (!empty($searchFilters['name'])) {
                $searchTerm = $searchFilters['name'];
                $globalQuery->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhereRaw("LOWER(alternative_names) LIKE ?", ["%" . mb_strtolower($searchTerm) . "%"]);
                });
            }

            if (!empty($searchFilters['type'])) {
                $globalQuery->where('type', $searchFilters['type']);
            }

            $globalResults = $globalQuery->limit(10)->get()->map(function ($identity) {
                $dates = trim("{$identity->birth_year} - {$identity->death_year}");
                $label = $identity->name . ($dates !== ' - ' ? " ({$dates})" : '');

                return [
                    'id' => 'global-' . $identity->id,
                    'value' => 'global-' . $identity->id,
                    'label' => $label . ' (' . __('hiko.global') . ')',
                ];
            });
        }

        // Search Global Professions (if type is profession)
        $professionResults = [];
        if ($request->input('type') === 'profession') {
            Tenancy::central(function () use (&$professionResults, $request) {
                $professionResults = GlobalProfession::query()
                    ->where('name', 'like', '%' . $request->input('search') . '%')
                    ->limit(10)
                    ->get()
                    ->map(function ($globalProfession) {
                        return [
                            'id' => 'global-' . $globalProfession->id,
                            'value' => 'global-' . $globalProfession->id,
                            'label' => $globalProfession->name ? "{$globalProfession->name} (Global)" : 'No Name (Global)',
                        ];
                    })
                    ->toArray();
            });
        }

        // Merge all results: Local Identities + Global Identities + Global Professions
        return $localResults
            ->merge($globalResults)
            ->merge($professionResults)
            ->values()
            ->toArray();
    }
}
