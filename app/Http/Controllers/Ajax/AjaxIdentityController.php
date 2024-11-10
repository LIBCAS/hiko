<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Services\SearchIdentity;
use App\Http\Controllers\Controller;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\GlobalProfession;

class AjaxIdentityController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (empty($request->query('search'))) {
            return [];
        }

        // Initialize SearchIdentity with provided filters
        $searchFilters = ['name' => $request->input('search')];
        $search = new SearchIdentity;

        // Retrieve and format local results
        $results = $search($searchFilters)
            ->map(function ($identity) {
                return [
                    'id' => 'local-' . $identity['id'],
                    'value' => 'local-' . $identity['id'],
                    'label' => $identity['label'] ?? 'No Name (Local)',
                ];
            })
            ->toArray();

        // Retrieve and format global results within central context
        Tenancy::central(function () use (&$results, $request) {
            $globalResults = GlobalProfession::query()
                ->where('name', 'like', '%' . $request->input('search') . '%')
                ->get()
                ->map(function ($globalProfession) {
                    return [
                        'id' => 'global-' . $globalProfession->id,
                        'value' => 'global-' . $globalProfession->id,
                        'label' => $globalProfession->name ? "{$globalProfession->name} (Global)" : 'No Name (Global)',
                    ];
                })
                ->toArray();

            $results = array_merge($results, $globalResults);
        });

        return $results;
    }
}
