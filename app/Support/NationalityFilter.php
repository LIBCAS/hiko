<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class NationalityFilter
{
    public static function apply($query, string $term): void
    {
        $source = ($query instanceof Builder ? $query->getQuery() : $query)->from;
        $pivot = NationalitySchema::pivotName($source);
        $key = $source === 'global_identities' ? 'global_identity_id' : 'identity_id';
        $query->whereExists(function ($sub) use ($source, $pivot, $key, $term) {
            $sub->selectRaw('1')->from($pivot . ' as nationality_assignments')
                ->join('nationalities as nationality_catalogue', 'nationality_catalogue.id', '=', 'nationality_assignments.nationality_id')
                ->whereColumn('nationality_assignments.' . $key, $source . '.id')
                ->where(function ($names) use ($term) {
                    foreach (['cs', 'en'] as $locale) {
                        $column = $names->getGrammar()->wrap('nationality_catalogue.name->' . $locale);
                        $names->orWhereRaw('LOWER(' . $column . ') LIKE ?', ['%' . mb_strtolower($term) . '%']);
                    }
                });
        });
    }
}
