<?php

namespace App\Services;

use App\Models\Letter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;

class LetterFilterService
{
    public const MATCH_ALL = 'all';
    public const MATCH_ANY = 'any';
    public const IDENTITY_FILTERS = ['author', 'recipient', 'mentioned'];
    public const ENTITY_FILTERS = ['author', 'recipient', 'mentioned', 'origin', 'destination', 'keyword', 'repository', 'archive', 'collection'];
    private const MANIFESTATION_FIELDS = ['preservation', 'type', 'copy', 'manifestation_notes', 'l_number', 'repository', 'archive', 'collection', 'signature', 'location_note'];
    private const OPERATORS = [
        'eq' => '=',
        'gte' => '>=',
        'lte' => '<='
    ];

    public static function allowedFilters(): array
    {
        return LetterFilterDefinitions::keys();
    }

    public function filteredQuery(array $filters, array $with = []): Builder
    {
        $prefix = tenancy()->initialized ? tenancy()->tenant->getAttribute('table_prefix') . '__' : '';
        $table = "{$prefix}letters";
        $query = Letter::query()->select("{$table}.*")->from($table);

        if ($with !== []) {
            $query->with($with);
        }

        return $this->apply($query, $filters, $prefix);
    }

    /** Accept historical scalar URLs as well as typed controls; never accept SQL identifiers/operators from input. */
    public function normalize(array $filters): array
    {
        $normalized = ['match' => ($filters['match'] ?? null) === 'any' ? 'any' : 'all'];

        foreach (LetterFilterDefinitions::fields() as $key => [$type]) {
            $value = $filters[$key] ?? null;

            if ($type === 'text') {
                $operator = is_array($value) ? $this->scalar($value['operator'] ?? 'contains') : 'contains';

                if (in_array($operator, ['empty', 'not_empty'], true)) {
                    $normalized[$key] = ['operator' => $operator];
                } else {
                    $text = $this->scalar(is_array($value) ? ($value['value'] ?? null) : $value);

                    if ($operator === 'contains' && $text !== '') {
                        $normalized[$key] = is_array($value) ? ['operator' => 'contains', 'value' => $text] : $text;
                    }
                }
            } elseif (in_array($type, ['count', 'part'], true)) {
                if ($type === 'part' && is_array($value) && ($value['operator'] ?? '') === 'empty') {
                    $normalized[$key] = ['operator' => 'empty'];
                    continue;
                }

                $operator = is_array($value) ? $this->scalar($value['operator'] ?? 'eq') : 'eq';
                $number = is_array($value) ? ($value['value'] ?? null) : $value;
                $max = $type === 'count' ? PHP_INT_MAX : (str_ends_with($key, 'year') ? 9999 : (str_ends_with($key, 'month') ? 12 : 31));

                if (is_scalar($number) && filter_var($number, FILTER_VALIDATE_INT) !== false
                    && (int) $number >= ($type === 'count' ? 0 : 1) && (int) $number <= $max
                    && isset(self::OPERATORS[$operator])) {
                    $normalized[$key] = ['operator' => $operator, 'value' => (int) $number];
                }
            } elseif (in_array($type, ['entity', 'languages', 'checks'], true)) {
                $values = array_values(array_unique(array_filter(array_map(fn ($v) => $this->scalar($v), is_array($value) ? $value : [$value]), fn ($v) => $v !== '')));

                if ($type === 'checks') {
                    $values = array_values(array_intersect($values, array_map('strval', array_keys(LetterFilterDefinitions::choices($key)))));
                }

                if ($values !== []) {
                    // Scalars retain the meaning of old name/substring URLs
                    $normalized[$key] = is_array($value) || in_array($key, self::IDENTITY_FILTERS, true) ? $values : $values[0];

                    if (in_array($type, ['entity', 'languages'], true) && ($filters["{$key}_match"] ?? '') === 'all') {
                        $normalized["{$key}_match"] = 'all';
                    }
                }
            } elseif ($type === 'boolean') {
                $value = is_bool($value) ? (string) (int) $value : $this->scalar($value);

                if (in_array($value, ['0', '1'], true)) {
                    $normalized[$key] = $value;
                }
            } elseif ($type === 'date') {
                $value = $this->scalar($value);

                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                    $normalized[$key] = $value;
                }
            } elseif ($type !== 'date_match') {
                $value = $this->scalar($value);

                if ($value !== '') {
                    $normalized[$key] = $value;
                }
            }
        }
        if ((isset($normalized['after']) || isset($normalized['before'])) && ($filters['date_match'] ?? '') === 'within') {
            $normalized['date_match'] = 'within';
        }

        return $normalized;
    }

    private function scalar(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    public function parseIds(mixed $value): array
    {
        $parts = is_array($value) ? $value : (preg_split('/[\s,;]+/', $this->scalar($value), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return collect($parts)->filter(fn ($v) => is_scalar($v) && filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false)
            ->map(fn ($v) => (int) $v)->unique()->values()->all();
    }

    public function apply(Builder $query, array $filters, string $prefix): Builder
    {
        $filters = $this->normalize($filters);
        $criteria = [];
        $all = $filters['match'] === 'all';

        if (isset($filters['id'])) {
            $criteria[] = fn ($q) => $q->whereIn("{$prefix}letters.id", $this->parseIds($filters['id']));
        }

        if (isset($filters['after']) || isset($filters['before'])) {
            // Both ends form one interval criterion, including in match-any mode
            $criteria[] = fn ($q) => $this->dateInterval($q, $filters, $prefix);
        }

        foreach (['author', 'recipient', 'mentioned', 'origin', 'destination'] as $role) {
            $fields = array_values(array_filter([$role, "{$role}_marked", $role === 'recipient' ? 'recipient_salutation' : ''], fn ($k) => isset($filters[$k])));
            $this->addRelatedCriteria($criteria, $filters, $fields, $all, fn ($q, $f) => $this->assignmentCriterion($q, $f, $role, $prefix));
        }

        $fields = array_values(array_filter(self::MANIFESTATION_FIELDS, fn ($k) => isset($filters[$k])));
        $this->addRelatedCriteria($criteria, $filters, $fields, $all, fn ($q, $f) => $this->manifestationCriterion($q, $f));
        $fields = array_values(array_filter(['resource_title', 'resource_link'], fn ($k) => isset($filters[$k])));
        $this->addRelatedCriteria($criteria, $filters, $fields, $all, fn ($q, $f) => $this->resourceCriterion($q, $f, $prefix));

        foreach (LetterFilterDefinitions::fields() as $field => [$type]) {
            if (!isset($filters[$field]) || in_array($field, array_merge(['id', 'after', 'before', 'date_match', 'resource_title', 'resource_link'], self::MANIFESTATION_FIELDS, self::ENTITY_FILTERS, ['author_marked', 'recipient_marked', 'recipient_salutation', 'origin_marked', 'destination_marked']), true)) {
                continue;
            }

            $value = $filters[$field];

            if ($type === 'count') {
                $criteria[] = fn ($q) => $this->countCriterion($q, $field, $value, $prefix);
            } elseif ($type === 'part') {
                $criteria[] = function ($q) use ($field, $value, $prefix) {
                    if (str_starts_with($field, 'range_')) {
                        $q->where("{$prefix}letters.date_is_range", 1);
                    }

                    if ($value['operator'] === 'empty') {
                        $q->where(fn ($nested) => $nested->whereNull("{$prefix}letters.{$field}")->orWhere("{$prefix}letters.{$field}", 0));
                    } else {
                        $q->where("{$prefix}letters.{$field}", self::OPERATORS[$value['operator']], $value['value']);
                    }
                };
            } elseif ($type === 'text') {
                $criteria[] = function ($q) use ($field, $prefix, $value) {
                    if ($field === 'abstract') {
                        $q->where(function ($translations) use ($prefix, $value) {
                            foreach (['cs', 'en'] as $locale) {
                                $translations->where(fn ($t) => $this->text($t, "{$prefix}letters.abstract->{$locale}", $value), null, null, $this->isEmpty($value) ? 'and' : 'or');
                            }
                        });
                    } else {
                        $this->text($q, "{$prefix}letters." . (in_array($field, ['abstract_cs', 'abstract_en']) ? 'abstract->' . substr($field, -2) : $field), $value);
                    }
                };
            } elseif ($type === 'boolean' && $field !== 'media') {
                $criteria[] = fn ($q) => $q->where("{$prefix}letters.{$field}", $value);
            } elseif ($field === 'media') {
                $criteria[] = fn ($q) => $value === '1' ? $q->has('media') : $q->doesntHave('media');
            } elseif ($field === 'languages') {
                $criteria[] = fn ($q) => $this->languageCriterion($q, $value, $filters['languages_match'] ?? 'any', $prefix);
            } elseif (in_array($field, ['status', 'approval'], true)) {
                $criteria[] = fn ($q) => $q->whereIn("{$prefix}letters.{$field}", (array) $value);
            } elseif ($field === 'editor') {
                $criteria[] = fn ($q) => $q->whereHas('users', fn ($users) => $this->text($users, 'name', $value));
            }
        }

        if (isset($filters['keyword'])) {
            $criteria[] = fn ($q) => $this->keywordCriterion($q, $filters, $prefix);
        }

        if ($criteria !== []) {
            $query->where(function ($group) use ($criteria, $all) {
                foreach ($criteria as $criterion) {
                    $group->where(fn ($nested) => $criterion($nested), null, null, $all ? 'and' : 'or');
                }
            });
        }

        return $query;
    }

    private function addRelatedCriteria(array &$criteria, array $filters, array $fields, bool $all, callable $apply): void
    {
        if ($fields === []) {
            return;
        }

        foreach ($all ? [$fields] : array_map(fn ($f) => [$f], $fields) as $group) {
            $subset = array_intersect_key($filters, array_flip(array_merge($group, array_map(fn ($f) => "{$f}_match", $group))));
            $criteria[] = fn ($q) => $apply($q, $subset);
        }
    }

    private function isEmpty(mixed $value): bool
    {
        return is_array($value) && ($value['operator'] ?? null) === 'empty';
    }

    private function text(Builder|QueryBuilder $query, string $column, mixed $value): void
    {
        $operator = is_array($value) ? ($value['operator'] ?? 'contains') : 'contains';
        $wrapped = $query->getGrammar()->wrap($column);
        $expression = "TRIM(COALESCE({$wrapped}, ''))";

        // Distinguish JSON null from the legitimate text "null" in MySQL
        if (str_contains($column, '->') && $query->getGrammar() instanceof MySqlGrammar) {
            [$base, $path] = explode('->', $column, 2);
            $base = $query->getGrammar()->wrap($base);
            $expression = "TRIM(CASE WHEN JSON_TYPE(JSON_EXTRACT({$base}, '$.{$path}')) = 'NULL' THEN '' ELSE COALESCE({$wrapped}, '') END)";
        }
        if ($operator === 'empty' || $operator === 'not_empty') {
            $blank = "TRIM(REPLACE(REPLACE(REPLACE({$expression}, CHAR(9), ''), CHAR(10), ''), CHAR(13), ''))";
            $query->whereRaw("{$blank} " . ($operator === 'empty' ? '=' : '<>') . " ''");
        } else {
            $search = is_array($value) ? $value['value'] : $value;
            // Use an explicit escape character so % and _ in scholarly text are literal
            $search = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search));
            $query->whereRaw("LOWER({$expression}) LIKE ? ESCAPE '!'", ["%{$search}%"]);
        }
    }

    private function assignmentQuery(Builder $query, string $role, string $prefix): QueryBuilder
    {
        $identity = in_array($role, self::IDENTITY_FILTERS, true);
        $pivot = $prefix . ($identity ? 'identity_letter' : 'letter_place');

        return $query->getQuery()->newQuery()->from($pivot . ' as assignment')
            ->whereColumn('assignment.letter_id', "{$prefix}letters.id")->where('assignment.role', $role);
    }

    private function assignmentCriterion(Builder $query, array $filters, string $role, string $prefix): void
    {
        $base = $this->assignmentQuery($query, $role, $prefix);

        foreach (["{$role}_marked" => 'marked', 'recipient_salutation' => 'salutation'] as $key => $column) {
            if (!isset($filters[$key])) {
                continue;
            }

            if ($this->isEmpty($filters[$key])) {
                // Emptiness applies to every assignment, independently of any positive marked-name condition already added to the witness
                $nonempty = $this->assignmentQuery($query, $role, $prefix);
                $this->text($nonempty, "assignment.{$column}", ['operator' => 'not_empty']);
                $query->whereNotExists($nonempty);
            } else {
                $this->text($base, "assignment.{$column}", $filters[$key]);
            }
        }

        $hasPositiveText = collect($filters)->contains(fn ($v, $k) => in_array($k, ["{$role}_marked", 'recipient_salutation']) && !$this->isEmpty($v));

        if (isset($filters[$role])) {
            $values = (array) $filters[$role];

            foreach (($filters["{$role}_match"] ?? 'any') === 'all' ? array_map(fn ($v) => [$v], $values) : [$values] as $selection) {
                $sub = clone $base;
                $sub->where(function ($group) use ($selection, $role, $prefix) {
                    foreach ($selection as $value) {
                        $group->orWhere(fn ($q) => $this->assignmentSelection($q, $role, $value, $prefix));
                    }
                });
                $query->whereExists($sub);
            }
        } elseif ($hasPositiveText) {
            $query->whereExists($base);
        }
    }

    private function assignmentSelection(QueryBuilder $query, string $role, string $value, string $prefix): void
    {
        $identity = in_array($role, self::IDENTITY_FILTERS, true);

        if (preg_match('/^(local|global)-(\d+)$/', $value, $m)) {
            if (!$identity) {
                $query->where('assignment.' . ($m[1] === 'local' ? 'place_id' : 'global_place_id'), (int) $m[2]);
            } elseif ($m[1] === 'local') {
                $query->where('assignment.identity_id', (int) $m[2]);
            } else {
                // Global identities are a search layer; only linked local identities are assigned
                $query->whereIn('assignment.identity_id', function ($q) use ($prefix, $m) {
                    $q->select('id')->from("{$prefix}identities")->where('global_identity_id', (int) $m[2]);
                });
            }

            return;
        }

        // Preserve old name-search URLs, including alternative names
        $query->where(function ($names) use ($identity, $prefix, $value) {
            foreach ($identity ? ['local'] : ['local', 'global'] as $scope) {
                $table = ($scope === 'local' ? $prefix : 'global_') . ($identity ? 'identities' : 'places');
                $key = $identity ? 'identity_id' : ($scope === 'local' ? 'place_id' : 'global_place_id');
                $names->orWhereIn("assignment.{$key}", function ($q) use ($table, $value) {
                    $q->select('id')->from($table)->where(function ($n) use ($value) {
                        $n->where(fn ($a) => $this->text($a, 'name', $value))->orWhere(fn ($a) => $this->text($a, 'alternative_names', $value));
                    });
                });
            }
        });
    }

    private function manifestationCriterion(Builder $query, array $filters): void
    {
        $positive = [];

        foreach ($filters as $field => $value) {
            if (str_ends_with($field, '_match')) {
                continue;
            }

            if ($this->isEmpty($value)) {
                $query->whereDoesntHave('manifestations', fn ($m) => $this->text($m, $field, ['operator' => 'not_empty']));
            } else {
                $positive[$field] = $value;
            }
        }

        if ($positive === []) {
            return;
        }

        // Every selected location must have a manifestation that also satisfies the other fields, without requiring every Cartesian combination
        $variants = [$positive];

        foreach (['repository', 'archive', 'collection'] as $field) {
            if (isset($positive[$field]) && ($filters["{$field}_match"] ?? 'any') === 'all') {
                foreach ((array) $positive[$field] as $value) {
                    $variants[] = array_replace($positive, [$field => [$value]]);
                }
            }
        }

        foreach ($variants as $variant) {
            $query->whereHas('manifestations', function ($m) use ($variant) {
                foreach ($variant as $field => $value) {
                    if (in_array($field, ['repository', 'archive', 'collection'])) {
                        $m->where(function ($locations) use ($field, $value) {
                            foreach ((array) $value as $selection) {
                                $locations->orWhere(function ($location) use ($field, $selection) {
                                    if (preg_match('/^(local|global)-(\d+)$/', $selection, $match)) {
                                        $location->where(($match[1] === 'global' ? 'global_' : '') . $field . '_id', (int) $match[2]);
                                    } else {
                                        $location->whereHas($field, fn ($q) => $this->text($q, 'name', $selection))
                                            ->orWhereHas('global' . ucfirst($field), fn ($q) => $this->text($q, 'name', $selection));
                                    }
                                });
                            }
                        });
                    } elseif (in_array($field, ['type', 'copy', 'preservation'])) {
                        $m->whereIn($field, (array) $value);
                    } else {
                        $this->text($m, $field, $value);
                    }
                }
            });
        }
    }

    private function keywordCriterion(Builder $query, array $filters, string $prefix): void
    {
        $values = (array) $filters['keyword'];

        foreach (($filters['keyword_match'] ?? 'any') === 'all' ? array_map(fn ($v) => [$v], $values) : [$values] as $selection) {
            $query->whereExists(function ($sub) use ($prefix, $selection) {
                $sub->selectRaw('1')->from("{$prefix}keyword_letter as selected_keyword")
                    ->whereColumn('selected_keyword.letter_id', "{$prefix}letters.id")
                    ->where(function ($group) use ($selection, $prefix) {
                        foreach ($selection as $value) {
                            if (preg_match('/^(local|global)-(\d+)$/', $value, $m)) {
                                $group->orWhere('selected_keyword.' . ($m[1] === 'local' ? 'keyword_id' : 'global_keyword_id'), (int) $m[2]);
                            } else {
                                foreach (['local', 'global'] as $scope) {
                                    $group->orWhereIn('selected_keyword.' . ($scope === 'local' ? 'keyword_id' : 'global_keyword_id'), function ($q) use ($prefix, $scope, $value) {
                                        $q->select('id')->from(($scope === 'local' ? $prefix : 'global_') . 'keywords');
                                        $this->text($q, 'name', $value);
                                    });
                                }
                            }
                        }
                    });
            });
        }
    }

    private function languageCriterion(Builder $query, mixed $values, string $match, string $prefix): void
    {
        if (!is_array($values)) {
            $this->text($query, "{$prefix}letters.languages", $values);
            return;
        }

        foreach ($match === 'all' ? array_map(fn ($v) => [$v], $values) : [$values] as $selection) {
            $rows = $this->languageRows($query, $prefix);
            $rows->whereIn(DB::raw('LOWER(TRIM(filter_language.value))'), array_map('mb_strtolower', $selection));

            // A scalar count avoids MySQL's semijoin rewrite of correlated JSON_TABLE EXISTS queries, which can incorrectly lose matches
            $this->whereCount($query, $rows, '>', 0);
        }
    }

    private function languageRows(Builder $query, string $prefix): QueryBuilder
    {
        $column = $query->getGrammar()->wrap("{$prefix}letters.languages");

        // JSON_QUOTE preserves quotes/backslashes while splitting the legacy delimiter
        if ($query->getGrammar() instanceof SQLiteGrammar) {
            $source = "json_each('[' || REPLACE(JSON_QUOTE(COALESCE({$column}, '')), ';', '\",\"') || ']') as filter_language";
        } else {
            $source = "JSON_TABLE(CONCAT('[', REPLACE(JSON_QUOTE(COALESCE({$column}, '')), ';', '\",\"'), ']'), '$[*]' COLUMNS(value TEXT PATH '$')) as filter_language";
        }

        return $query->getQuery()->newQuery()->fromRaw($source)->whereRaw("TRIM(filter_language.value) <> ''");
    }

    private function resourceRows(Builder $query, string $prefix): QueryBuilder
    {
        $column = $query->getGrammar()->wrap("{$prefix}letters.related_resources");

        if ($query->getGrammar() instanceof SQLiteGrammar) {
            return $query->getQuery()->newQuery()->fromRaw("json_each(COALESCE({$column}, '[]')) as resource");
        }

        return $query->getQuery()->newQuery()->fromRaw("JSON_TABLE(COALESCE({$column}, JSON_ARRAY()), '$[*]' COLUMNS(title TEXT PATH '$.title', link TEXT PATH '$.link')) as resource");
    }

    private function resourceCriterion(Builder $query, array $filters, string $prefix): void
    {
        $base = $this->resourceRows($query, $prefix);
        $positive = false;

        foreach ($filters as $field => $value) {
            $column = $field === 'resource_title' ? 'title' : 'link';
            $column = $query->getGrammar() instanceof SQLiteGrammar ? "resource.value->{$column}" : "resource.{$column}";

            if ($this->isEmpty($value)) {
                $sub = $this->resourceRows($query, $prefix);
                $this->text($sub, $column, ['operator' => 'not_empty']);
                $this->whereCount($query, $sub, '=', 0);
            } else {
                $this->text($base, $column, $value);
                $positive = true;
            }
        }

        if ($positive) {
            $this->whereCount($query, $base, '>', 0);
        }
    }

    private function whereCount(Builder $query, QueryBuilder $rows, string $operator, int $count): void
    {
        $subquery = $rows->selectRaw('COUNT(*)');

        // Explicitly bind the scalar subquery before the comparison value
        $query->getQuery()->addBinding($subquery->getBindings(), 'where');
        $query->where(DB::raw('(' . $subquery->toSql() . ')'), $operator, $count);
    }

    private function countCriterion(Builder $query, string $field, array $value, string $prefix): void
    {
        $operator = self::OPERATORS[$value['operator']];
        $role = substr($field, 0, -6);

        if (in_array($role, ['author', 'recipient', 'mentioned', 'origin', 'destination'])) {
            $this->whereCount($query, $this->assignmentQuery($query, $role, $prefix), $operator, $value['value']);
        } elseif ($role === 'keyword') {
            $rows = $query->getQuery()->newQuery()->from("{$prefix}keyword_letter")
                ->whereColumn('letter_id', "{$prefix}letters.id");
            $this->whereCount($query, $rows, $operator, $value['value']);
        } elseif ($role === 'manifestations') {
            $query->has('manifestations', $operator, $value['value']);
        } elseif ($role === 'related_resources') {
            $this->whereCount($query, $this->resourceRows($query, $prefix), $operator, $value['value']);
        } elseif ($role === 'languages') {
            $this->whereCount($query, $this->languageRows($query, $prefix), $operator, $value['value']);
        }
    }

    private function dateBound(Builder $query, string $prefix, bool $upper): string
    {
        $wrap = fn ($field) => $query->getGrammar()->wrap("{$prefix}letters.{$field}");
        $year = $wrap('date_year');
        $month = $wrap('date_month');
        $day = $wrap('date_day');

        if ($upper) {
            $isRange = $wrap('date_is_range');
            // A missing range year is an open upper end, rather than an invented year
            $year = "(CASE WHEN {$isRange} = 1 THEN NULLIF(" . $wrap('range_year') . ", 0) ELSE {$year} END)";
            $month = "(CASE WHEN {$isRange} = 1 THEN " . $wrap('range_month') . " ELSE {$month} END)";
            $day = "(CASE WHEN {$isRange} = 1 THEN " . $wrap('range_day') . " ELSE {$day} END)";
        }

        $m = "COALESCE(NULLIF({$month}, 0), " . ($upper ? '12' : '1') . ')';
        $lastDay = "CASE WHEN {$m} IN (4,6,9,11) THEN 30 WHEN {$m} = 2 THEN CASE WHEN ({$year} % 400 = 0 OR ({$year} % 4 = 0 AND {$year} % 100 <> 0)) THEN 29 ELSE 28 END ELSE 31 END";
        $d = "COALESCE(NULLIF({$day}, 0), " . ($upper ? "({$lastDay})" : '1') . ')';

        return "(COALESCE(NULLIF({$year}, 0), " . ($upper ? '9999' : '1') . ") * 10000 + {$m} * 100 + {$d})";
    }

    private function dateInterval(Builder $query, array $filters, string $prefix): void
    {
        $query->where("{$prefix}letters.date_year", '>', 0);
        $within = ($filters['date_match'] ?? 'overlap') === 'within';

        if (isset($filters['after'])) {
            $query->whereRaw($this->dateBound($query, $prefix, !$within) . ' >= ?', [(int) str_replace('-', '', $filters['after'])]);
        }

        if (isset($filters['before'])) {
            $query->whereRaw($this->dateBound($query, $prefix, $within) . ' <= ?', [(int) str_replace('-', '', $filters['before'])]);
        }
    }
}
