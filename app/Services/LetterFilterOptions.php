<?php

namespace App\Services;

use App\Models\{Identity, GlobalIdentity, Place, GlobalPlace, Keyword, GlobalKeyword, Location, GlobalLocation};

class LetterFilterOptions
{
    private function models(string $field): array
    {
        return match ($field) {
            'author', 'recipient', 'mentioned' => [Identity::class, GlobalIdentity::class],
            'origin', 'destination' => [Place::class, GlobalPlace::class],
            'keyword' => [Keyword::class, GlobalKeyword::class],
            'repository', 'archive', 'collection' => [Location::class, GlobalLocation::class],
            default => [],
        };
    }

    public function options(string $field, array $values = [], ?string $search = null): array
    {
        $options = [];

        foreach ($this->models($field) as $index => $model) {
            $scope = $index === 0 ? 'local' : 'global';
            $query = $model::query();

            if ($search === null) {
                $ids = collect($values)->filter(fn ($v) => is_string($v) && preg_match('/^' . $scope . '-\d+$/', $v))->map(fn ($v) => (int) substr($v, strlen($scope) + 1))->all();

                if ($ids === []) {
                    continue;
                }

                $query->whereKey($ids);
            } else {
                $needle = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($search)) . '%';
                $query->where(function ($q) use ($needle, $field) {
                    $q->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$needle]);
                    if (in_array($field, ['author', 'recipient', 'mentioned', 'origin', 'destination'])) {
                        $q->orWhereRaw("LOWER(alternative_names) LIKE ? ESCAPE '!'", [$needle]);
                    }
                });

                if (in_array($field, ['repository', 'archive', 'collection'])) {
                    $query->where('type', $field);
                }

                $query->orderBy('name')->limit(50);
            }

            foreach ($query->get() as $record) {
                $value = "{$scope}-{$record->id}";
                $name = $field === 'keyword' ? $record->getTranslation('name', app()->getLocale()) : $record->name;

                if (in_array($field, ['origin', 'destination'])) {
                    $name = implode(', ', array_filter([$name, $record->division, $record->country]));
                }

                $options[$value] = ['value' => $value, 'label' => $name . ' (' . __("hiko.{$scope}") . ')'];
            }
        }

        if ($search !== null) {
            return collect($options)->sortBy('label')->values()->all();
        }

        return array_map(fn ($v) => $options[$v] ?? ['value' => $v, 'label' => $v], $values);
    }

    public function languages(): array
    {
        return collect(json_decode(file_get_contents(resource_path('data/languages.json')), true))->pluck('name')->sort()->values()->all();
    }
}
