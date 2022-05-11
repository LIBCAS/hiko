<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Identity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SimilarNamesController extends Controller
{
    public function __invoke(Request $request)
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
                    if ($this->similar($request->query('search'), $name)) {
                        $similarAlternativeName = true;
                        break;
                    }
                }

                $hasSimilarName = $this->similar($request->query('search'), $identity->name) || $similarAlternativeName;

                return !$hasSimilarName;
            })
            ->map(function ($identity) {
                return [
                    'id' => $identity->id,
                    'value' => $identity->id,
                    'label' => "{$identity->name} ({$identity->birth_year}-{$identity->death_year})",
                ];
            })
            ->toArray();
    }

    protected function similar($string1, $string2)
    {
        return levenshtein(
            strtolower(removeAccents($string1)),
            strtolower(removeAccents($string2)),
        ) <= 3;
    }
}
