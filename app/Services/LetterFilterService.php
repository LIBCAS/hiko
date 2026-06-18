<?php

namespace App\Services;

use App\Models\Letter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LetterFilterService
{
    public const ALLOWED_FILTERS = [
        'id', 'signature', 'author', 'recipient',
        'origin', 'destination', 'repository', 'archive', 'collection',
        'keyword', 'mentioned', 'content_stripped', 'abstract',
        'languages', 'notes_private', 'media', 'status', 'approval', 'editor',
        'after', 'before',
    ];

    public function filteredQuery(array $filters, array $with = []): Builder
    {
        $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
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
        if (!empty($filters['id'])) {
            $ids = $this->parseIds($filters['id']);
            $ids === []
                ? $query->whereRaw('1 = 0')
                : $query->whereIn("{$prefix}letters.id", $ids);
        }

        foreach (['repository', 'archive', 'collection', 'signature'] as $field) {
            if (!empty($filters[$field])) {
                $query->whereRaw(
                    "LOWER(JSON_UNQUOTE(JSON_EXTRACT(copies, '$[*].{$field}'))) LIKE ?",
                    ['%' . mb_strtolower($filters[$field]) . '%']
                );
            }
        }

        foreach (['author', 'recipient', 'mentioned'] as $role) {
            if (!empty($filters[$role])) {
                $query->whereExists(function ($sub) use ($filters, $prefix, $role) {
                    $sub->select(DB::raw(1))
                        ->from("{$prefix}identity_letter")
                        ->join("{$prefix}identities", "{$prefix}identity_letter.identity_id", '=', "{$prefix}identities.id")
                        ->whereColumn("{$prefix}identity_letter.letter_id", "{$prefix}letters.id")
                        ->where('role', $role)
                        ->where('name', 'like', '%' . $filters[$role] . '%');
                });
            }
        }

        foreach (['origin', 'destination'] as $role) {
            if (!empty($filters[$role])) {
                $query->where(function ($q) use ($filters, $prefix, $role) {
                    $q->whereExists(function ($sub) use ($filters, $prefix, $role) {
                        $sub->select(DB::raw(1))
                            ->from("{$prefix}letter_place")
                            ->join("{$prefix}places", "{$prefix}letter_place.place_id", '=', "{$prefix}places.id")
                            ->whereColumn("{$prefix}letter_place.letter_id", "{$prefix}letters.id")
                            ->where('role', $role)
                            ->where(function ($names) use ($filters, $role) {
                                $names->where('name', 'like', '%' . $filters[$role] . '%');
                                for ($i = 0; $i < 50; $i++) {
                                    $names->orWhereRaw(
                                        "JSON_UNQUOTE(JSON_EXTRACT(alternative_names, '$[$i]')) LIKE ?",
                                        ['%' . $filters[$role] . '%']
                                    );
                                }
                            });
                    })->orWhereExists(function ($sub) use ($filters, $prefix, $role) {
                        $sub->select(DB::raw(1))
                            ->from("{$prefix}letter_place")
                            ->join('global_places', "{$prefix}letter_place.global_place_id", '=', 'global_places.id')
                            ->whereColumn("{$prefix}letter_place.letter_id", "{$prefix}letters.id")
                            ->where('role', $role)
                            ->where('name', 'like', '%' . $filters[$role] . '%');
                    });
                });
            }
        }

        foreach (['content_stripped', 'abstract', 'notes_private', 'languages'] as $field) {
            if (!empty($filters[$field])) {
                $query->whereRaw(
                    "LOWER({$prefix}letters.{$field}) LIKE ?",
                    ['%' . mb_strtolower($filters[$field]) . '%']
                );
            }
        }

        if (!empty($filters['keyword'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('localKeywords', function ($sub) use ($filters) {
                    $sub->where('name->cs', 'like', '%' . $filters['keyword'] . '%')
                        ->orWhere('name->en', 'like', '%' . $filters['keyword'] . '%');
                })->orWhereHas('globalKeywords', function ($sub) use ($filters) {
                    $sub->where('name->cs', 'like', '%' . $filters['keyword'] . '%')
                        ->orWhere('name->en', 'like', '%' . $filters['keyword'] . '%');
                });
            });
        }

        if (isset($filters['media']) && $filters['media'] !== '') {
            $filters['media'] === '1' ? $query->has('media') : $query->doesntHave('media');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['approval']) && $filters['approval'] !== '') {
            $query->where('approval', $filters['approval']);
        }

        if (!empty($filters['after'])) {
            $query->whereDate('date_computed', '>=', $filters['after']);
        }

        if (!empty($filters['before'])) {
            $query->whereDate('date_computed', '<=', $filters['before']);
        }

        if (!empty($filters['editor'])) {
            $editor = $filters['editor'];
            $query->whereHas('users', fn ($q) => $q->where('name', 'like', '%' . $editor . '%'));
        }

        return $query;
    }
}
