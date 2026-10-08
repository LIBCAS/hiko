<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class NationalityFilter
{
    public static function applyPresence($query, string $presence): void
    {
        if (!in_array($presence, ['yes', 'no'], true)) {
            return;
        }

        $source = ($query instanceof Builder ? $query->getQuery() : $query)->from;
        $pivot = NationalitySchema::pivotName($source);
        $key = $source === 'global_identities' ? 'global_identity_id' : 'identity_id';
        $method = $presence === 'yes' ? 'whereExists' : 'whereNotExists';
        $query->$method(function ($sub) use ($source, $pivot, $key) {
            $sub->selectRaw('1')->from($pivot . ' as nationality_presence')
                ->whereColumn('nationality_presence.' . $key, $source . '.id');
        });
    }

    public static function apply($query, string $term, string $mode = 'direct'): void
    {
        $source = ($query instanceof Builder ? $query->getQuery() : $query)->from;
        $pivot = NationalitySchema::pivotName($source);
        $key = $source === 'global_identities' ? 'global_identity_id' : 'identity_id';
        $query->whereExists(function ($sub) use ($source, $pivot, $key, $term, $mode) {
            $sub->selectRaw('1')->from($pivot . ' as nationality_assignments')
                ->join('nationalities as nationality_catalogue', 'nationality_catalogue.id', '=', 'nationality_assignments.nationality_id')
                ->whereColumn('nationality_assignments.' . $key, $source . '.id')
                ->where(function ($matches) use ($term, $mode) {
                    $matches->where(fn($names) => self::matchNames($names, 'nationality_catalogue', $term));
                    if ($mode === 'expanded') {
                        $matches->orWhereExists(function ($rules) use ($term) {
                            $rules->selectRaw('1')->from('nationality_search_expansions as expansion')
                                ->join('nationalities as expansion_source', 'expansion_source.id', '=', 'expansion.source_nationality_id')
                                ->whereColumn('expansion.target_nationality_id', 'nationality_catalogue.id')
                                ->where(fn($names) => self::matchNames($names, 'expansion_source', $term));
                        });
                    }
                });
        });
    }

    private static function matchNames($query, string $alias, string $term): void
    {
        foreach (['cs', 'en'] as $locale) {
            $column = $query->getGrammar()->wrap($alias . '.name->' . $locale);
            $query->orWhereRaw('LOWER(' . $column . ') LIKE ?', ['%' . mb_strtolower($term) . '%']);
        }
    }

    public static function applyFilters($query, array $filters): void
    {
        $term = trim($filters['nationality'] ?? '');
        if ($term !== '') {
            self::apply($query, $term, $filters['nationality_match'] ?? 'direct');
        }
    }
}
