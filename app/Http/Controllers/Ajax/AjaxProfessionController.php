<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Profession;
use App\Models\GlobalProfession;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

class AjaxProfessionController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (empty($request->query('search'))) {
            return [];
        }

        $search = Str::lower($request->query('search'));
        $results = [];

        // Fetch local professions
        $localProfessions = Profession::whereRaw('LOWER(name) like ?', ['%' . $search . '%'])
            ->select('id', 'name')
            ->take(10)
            ->get()
            ->map(function ($profession) {
                return [
                    'id' => 'local-' . $profession->id,
                    'value' => 'local-' . $profession->id,
                    'label' => "{$profession->name} (Local)",
                ];
            });

        $results = $localProfessions->toArray();

        // Fetch global professions within central context
        Tenancy::central(function () use ($search, &$results) {
            $globalProfessions = GlobalProfession::whereRaw("LOWER(JSON_UNQUOTE(name->'$.en')) like ?", ['%' . $search . '%'])
                ->select('id', 'name')
                ->take(10)
                ->get()
                ->map(function ($profession) {
                    return [
                        'id' => 'global-' . $profession->id,
                        'value' => 'global-' . $profession->id,
                        'label' => "{$profession->name} (Global)",
                    ];
                });
            $results = array_merge($results, $globalProfessions->toArray());
        });

        return $results;
    }
}
