<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Identity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SimilarNamesController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (!$request->has('search')) {
            return [];
        }

        $searchQuery = Identity::search($request->query('search'));

        return Identity::select('id', 'name', 'birth_year', 'death_year', 'alternative_names')
            ->whereIn('id', $searchQuery->keys()->toArray())
            ->get()
            ->reject(function ($identity) use ($request) {
                $similarAlternativeName = false;

                foreach ((array) $identity->alternative_names as $name) {
                    if (similar($request->query('search'), $name)) {
                        $similarAlternativeName = true;
                        break;
                    }
                }

                $hasSimilarName = similar($request->query('search'), $identity->name) || $similarAlternativeName;

                return !$hasSimilarName;
            })
            ->map(function ($identity) {
                return [
                    'id' => $identity->id,
                    'label' => "{$identity->name} {$identity->dates}",
                ];
            })
            ->toArray();
    }
}
