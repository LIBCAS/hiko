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

        $search = new SearchIdentity;

        $results = $search($request->input('search'))
            ->map(function ($identity) {
                return [
                    'id' => $identity['id'],
                    'value' => 'local-' . $identity['id'],
                    'label' => $identity['name'] ?? 'No Name (Local)',
                ];
            })
            ->toArray();

        Tenancy::central(function () use (&$results, $request, $search) {
            $globalResults = GlobalProfession::query()
                ->where('name', 'like', '%' . $request->input('search') . '%')
                ->get()
                ->map(function ($globalProfession) {
                    return [
                        'id' => $globalProfession->id,
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
