<?php

namespace App\Services;

use App\Models\Letter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class LetterFilterService
{
    public const MATCH_ALL = 'all';
    public const MATCH_ANY = 'any';

    public const ALLOWED_FILTERS = [
        'match', 'id', 'signature', 'author', 'recipient',
        'origin', 'destination', 'repository', 'archive', 'collection',
        'keyword', 'mentioned', 'content_stripped', 'abstract',
        'languages', 'notes_private', 'media', 'status', 'approval', 'editor',
        'after', 'before',
    ];

    public const IDENTITY_FILTERS = ['author', 'recipient', 'mentioned'];

    public function filteredQuery(array $filters, array $with = []): Builder
    {
        $filters = $this->normalize($filters);
        $prefix = tenancy()->initialized ? tenancy()->tenant->table_prefix . '__' : '';
        $lettersTable = "{$prefix}letters";

        $query = Letter::query()
            ->select("{$lettersTable}.*")
            ->from($lettersTable);

        if ($with !== []) {
            $query->with($with);
        }

        return $this->apply($query, $filters, $prefix);
    }

    public function normalize(array $filters): array
    {
        $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
        $normalized = [
            'match' => ($filters['match'] ?? self::MATCH_ALL) === self::MATCH_ANY
                ? self::MATCH_ANY
                : self::MATCH_ALL,
        ];

        foreach ($filters as $key => $value) {
            if ($key === 'match') {
                continue;
            }

            if (in_array($key, self::IDENTITY_FILTERS, true)) {
                $values = is_array($value) ? $value : [$value];
                $values = collect($values)
                    ->map(fn ($item) => trim((string) $item))
                    ->filter(fn ($item) => $item !== '')
                    ->unique()
                    ->values()
                    ->all();

                if ($values !== []) {
                    $normalized[$key] = $values;
                }

                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value !== null && $value !== '' && $value !== []) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    public function parseIds(mixed $value): array
    {
        $parts = is_array($value)
            ? $value
            : (preg_split('/[\s,;]+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return collect($parts)
            ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))
            ->filter(fn ($id) => $id !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function apply(Builder $query, array $filters, string $prefix): Builder
    {
        $filters = $this->normalize($filters);
        $criteria = $this->criteria($filters, $prefix);

        if ($criteria === []) {
            return $query;
        }

        if ($filters['match'] === self::MATCH_ANY) {
            return $query->where(function (Builder $group) use ($criteria) {
                foreach ($criteria as $criterion) {
                    $group->orWhere(fn (Builder $nested) => $criterion($nested));
                }
            });
        }

        foreach ($criteria as $criterion) {
            $criterion($query);
        }

        return $query;
    }

    /**
     * Build one grouped condition per active UI filter. Conditions selected inside
     * one identity field are alternatives; the top-level match mode combines fields.
     *
     * @return array<int, callable(Builder): void>
     */
    protected function criteria(array $filters, string $prefix): array
    {
        $criteria = [];

        if (isset($filters['id'])) {
            $ids = $this->parseIds($filters['id']);
            $criteria[] = function (Builder $query) use ($ids, $prefix) {
                $ids === []
                    ? $query->whereRaw('1 = 0')
                    : $query->whereIn("{$prefix}letters.id", $ids);
            };
        }

        foreach (self::IDENTITY_FILTERS as $role) {
            if (isset($filters[$role])) {
                $values = $filters[$role];
                $criteria[] = fn (Builder $query) => $this->applyIdentityCriterion(
                    $query,
                    $prefix,
                    $role,
                    $values
                );
            }
        }

        foreach (['origin', 'destination'] as $role) {
            if (isset($filters[$role])) {
                $search = $filters[$role];
                $criteria[] = fn (Builder $query) => $this->applyPlaceCriterion(
                    $query,
                    $prefix,
                    $role,
                    $search
                );
            }
        }

        foreach (['repository', 'archive', 'collection'] as $field) {
            if (isset($filters[$field])) {
                $search = $filters[$field];
                $criteria[] = fn (Builder $query) => $query->whereHas(
                    'manifestations',
                    function (Builder $manifestations) use ($field, $search) {
                        $globalRelation = 'global' . ucfirst($field);
                        $manifestations->where(function (Builder $locations) use ($field, $globalRelation, $search) {
                            $locations
                                ->whereHas($field, fn (Builder $location) => $this->whereNameContains($location, $search))
                                ->orWhereHas($globalRelation, fn (Builder $location) => $this->whereNameContains($location, $search));
                        });
                    }
                );
            }
        }

        if (isset($filters['signature'])) {
            $search = mb_strtolower((string) $filters['signature']);
            $criteria[] = fn (Builder $query) => $query->whereHas(
                'manifestations',
                fn (Builder $manifestations) => $this->whereLowerContains(
                    $manifestations,
                    'signature',
                    $search
                )
            );
        }

        foreach (['content_stripped', 'abstract', 'notes_private', 'languages'] as $field) {
            if (isset($filters[$field])) {
                $search = mb_strtolower((string) $filters[$field]);
                $criteria[] = fn (Builder $query) => $this->whereLowerContains(
                    $query,
                    "{$prefix}letters.{$field}",
                    $search
                );
            }
        }

        if (isset($filters['keyword'])) {
            $search = mb_strtolower((string) $filters['keyword']);
            $criteria[] = fn (Builder $query) => $query->where(function (Builder $keywords) use ($search) {
                $keywords
                    ->whereHas('localKeywords', fn (Builder $keyword) => $this->whereTranslatedNameContains($keyword, $search))
                    ->orWhereHas('globalKeywords', fn (Builder $keyword) => $this->whereTranslatedNameContains($keyword, $search));
            });
        }

        if (isset($filters['media'])) {
            $withMedia = (string) $filters['media'] === '1';
            $criteria[] = fn (Builder $query) => $withMedia
                ? $query->has('media')
                : $query->doesntHave('media');
        }

        if (isset($filters['status'])) {
            $status = $filters['status'];
            $criteria[] = fn (Builder $query) => $query->where("{$prefix}letters.status", $status);
        }

        if (isset($filters['approval'])) {
            $approval = $filters['approval'];
            $criteria[] = fn (Builder $query) => $query->where("{$prefix}letters.approval", $approval);
        }

        if (isset($filters['after'])) {
            $after = $filters['after'];
            $criteria[] = fn (Builder $query) => $query->whereDate("{$prefix}letters.date_computed", '>=', $after);
        }

        if (isset($filters['before'])) {
            $before = $filters['before'];
            $criteria[] = fn (Builder $query) => $query->whereDate("{$prefix}letters.date_computed", '<=', $before);
        }

        if (isset($filters['editor'])) {
            $editor = $filters['editor'];
            $criteria[] = fn (Builder $query) => $query->whereHas(
                'users',
                fn (Builder $users) => $users->where('name', 'like', "%{$editor}%")
            );
        }

        return $criteria;
    }

    protected function applyIdentityCriterion(
        Builder $query,
        string $prefix,
        string $role,
        array $values
    ): void {
        $localIds = [];
        $globalIds = [];
        $nameSearches = [];

        foreach ($values as $value) {
            if (preg_match('/^local-(\d+)$/', $value, $matches)) {
                $localIds[] = (int) $matches[1];
            } elseif (preg_match('/^global-(\d+)$/', $value, $matches)) {
                $globalIds[] = (int) $matches[1];
            } else {
                // Compatibility with filter values saved before identity selectors.
                $nameSearches[] = mb_strtolower($value);
            }
        }

        $query->whereExists(function ($sub) use (
            $prefix,
            $role,
            $localIds,
            $globalIds,
            $nameSearches
        ) {
            $pivot = "{$prefix}identity_letter";
            $identities = "{$prefix}identities";

            $sub->select(DB::raw(1))
                ->from($pivot)
                ->join($identities, "{$pivot}.identity_id", '=', "{$identities}.id")
                ->whereColumn("{$pivot}.letter_id", "{$prefix}letters.id")
                ->where("{$pivot}.role", $role)
                ->where(function ($identity) use ($identities, $localIds, $globalIds, $nameSearches) {
                    if ($localIds !== []) {
                        $identity->orWhereIn("{$identities}.id", array_values(array_unique($localIds)));
                    }

                    if ($globalIds !== []) {
                        $identity->orWhereIn(
                            "{$identities}.global_identity_id",
                            array_values(array_unique($globalIds))
                        );
                    }

                    foreach ($nameSearches as $search) {
                        $identity->orWhere(function ($name) use ($identities, $search) {
                            $this->whereLowerContains($name, "{$identities}.name", $search);
                            $this->whereLowerContains(
                                $name,
                                "{$identities}.alternative_names",
                                $search,
                                'or'
                            );
                        });
                    }

                    if ($localIds === [] && $globalIds === [] && $nameSearches === []) {
                        $identity->whereRaw('1 = 0');
                    }
                });
        });
    }

    protected function applyPlaceCriterion(
        Builder $query,
        string $prefix,
        string $role,
        string $search
    ): void {
        $search = mb_strtolower($search);

        $query->where(function (Builder $places) use ($prefix, $role, $search) {
            $pivot = "{$prefix}letter_place";
            $localPlaces = "{$prefix}places";

            $places
                ->whereExists(function ($sub) use ($prefix, $pivot, $localPlaces, $role, $search) {
                    $sub->select(DB::raw(1))
                        ->from($pivot)
                        ->join($localPlaces, "{$pivot}.place_id", '=', "{$localPlaces}.id")
                        ->whereColumn("{$pivot}.letter_id", "{$prefix}letters.id")
                        ->where("{$pivot}.role", $role)
                        ->where(function ($names) use ($localPlaces, $search) {
                            $this->whereLowerContains($names, "{$localPlaces}.name", $search);
                            $this->whereLowerContains(
                                $names,
                                "{$localPlaces}.alternative_names",
                                $search,
                                'or'
                            );
                        });
                })
                ->orWhereExists(function ($sub) use ($prefix, $pivot, $role, $search) {
                    $sub->select(DB::raw(1))
                        ->from($pivot)
                        ->join('global_places', "{$pivot}.global_place_id", '=', 'global_places.id')
                        ->whereColumn("{$pivot}.letter_id", "{$prefix}letters.id")
                        ->where("{$pivot}.role", $role)
                        ->where(function ($names) use ($search) {
                            $this->whereLowerContains($names, 'global_places.name', $search);
                            $this->whereLowerContains(
                                $names,
                                'global_places.alternative_names',
                                $search,
                                'or'
                            );
                        });
                });
        });
    }

    protected function whereNameContains(Builder $query, string $search): void
    {
        $search = mb_strtolower($search);
        $this->whereLowerContains($query, 'name', $search);
    }

    protected function whereTranslatedNameContains(Builder $query, string $search): void
    {
        // The JSON document contains only translated name values. Searching its
        // serialized form keeps this query portable between MySQL and SQLite tests.
        $this->whereLowerContains($query, 'name', $search);
    }

    protected function whereLowerContains(
        Builder|QueryBuilder $query,
        string $column,
        string $search,
        string $boolean = 'and'
    ): void {
        $wrappedColumn = $query->getGrammar()->wrap($column);

        $query->whereRaw(
            "LOWER({$wrappedColumn}) LIKE ?",
            ["%{$search}%"],
            $boolean
        );
    }
}
