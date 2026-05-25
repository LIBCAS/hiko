<?php

namespace App\Exports;

use App\Models\GlobalIdentity;
use App\Models\Identity;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class IdentitiesExport implements FromCollection, WithMapping, WithHeadings
{
    protected array $filters = [
        'name' => '',
        'related_names' => '',
        'type' => '',
        'profession' => '',
        'note' => '',
        'order' => 'name',
        'religion' => null,
        'source' => 'all',
        'global_identity' => '',
        'admin_notes' => '',
    ];

    public function __construct(array $filters = [])
    {
        $this->filters = array_replace(
            $this->filters,
            array_intersect_key($filters, $this->filters)
        );
    }

    public function collection()
    {
        $source = in_array($this->filters['source'] ?? 'all', ['all', 'local', 'global'], true)
            ? $this->filters['source']
            : 'all';
        $hasGlobalIdentityFilter = trim((string)($this->filters['global_identity'] ?? '')) !== '';
        $hasAdminNotesFilter = trim((string)($this->filters['admin_notes'] ?? '')) !== '';

        $localIdentities = collect();
        if (($source === 'all' || $source === 'local') && !$hasAdminNotesFilter && tenancy()->initialized) {
            $localIdentities = $this->applyLocalFilters(Identity::with([
                'professions',
                'globalProfessions',
                'profession_categories'
            ]))->get()->map(function ($identity) {
                $identity->source_type = 'Local';
                return $identity;
            });
        }

        $globalIdentities = collect();
        if (($source === 'all' || $source === 'global') && !$hasGlobalIdentityFilter) {
            $globalIdentities = $this->applyGlobalFilters(GlobalIdentity::with([
                'professions', // Note: GlobalIdentity model uses 'professions' for global_identity_profession
            ]))->get()->map(function ($identity) {
                $identity->source_type = 'Global';
                // Polyfill globalProfessions property to match local structure for mapping below
                $identity->globalProfessions = $identity->professions;
                return $identity;
            });
        }

        return $localIdentities
            ->toBase()
            ->concat($globalIdentities)
            ->values();
    }

    protected function applyLocalFilters(Builder $query): Builder
    {
        $filters = $this->filters;

        $query->when($filters['name'], fn($q) => $q->where('name', 'like', "%{$filters['name']}%"));
        $query->when($filters['related_names'], fn($q) => $q->where('related_names', 'like', "%{$filters['related_names']}%"));
        $query->when($filters['type'], fn($q) => $q->where('type', $filters['type']));
        $query->when($filters['note'], fn($q) => $q->where('note', 'like', "%{$filters['note']}%"));

        if (!empty($filters['global_identity'])) {
            $term = trim((string)$filters['global_identity']);
            $query->where(function ($q) use ($term) {
                $q->whereHas('globalIdentity', fn($sub) => $sub->where('name', 'like', "%{$term}%"));

                if (ctype_digit($term)) {
                    $q->orWhere('global_identity_id', (int)$term);
                }
            });
        }

        if (!empty($filters['religion'])) {
            $this->applyReligionFilter($query, trim((string)$filters['religion']));
        }

        if (!empty($filters['profession'])) {
            $term = trim((string)$filters['profession']);
            $query->where(function ($q) use ($term) {
                $q->whereHas('professions', fn($sub) => $this->whereTranslatableNameLike($sub, $term))
                    ->orWhereHas('globalProfessions', fn($sub) => $this->whereTranslatableNameLike($sub, $term));
            });
        }

        return $this->applyOrder($query);
    }

    protected function applyGlobalFilters(Builder $query): Builder
    {
        $filters = $this->filters;

        $query->when($filters['name'], fn($q) => $q->where('name', 'like', "%{$filters['name']}%"));
        $query->when($filters['related_names'], fn($q) => $q->where('related_names', 'like', "%{$filters['related_names']}%"));
        $query->when($filters['type'], fn($q) => $q->where('type', $filters['type']));
        $query->when($filters['note'], fn($q) => $q->where('note', 'like', "%{$filters['note']}%"));

        if (!empty($filters['admin_notes'])) {
            $adminNotes = trim((string)$filters['admin_notes']);
            $query->where('admin_notes', 'like', "%{$adminNotes}%");
        }

        if (!empty($filters['religion'])) {
            $this->applyReligionFilter($query, trim((string)$filters['religion']));
        }

        if (!empty($filters['profession'])) {
            $term = trim((string)$filters['profession']);
            $query->whereHas('professions', fn($sub) => $this->whereTranslatableNameLike($sub, $term));
        }

        return $this->applyOrder($query);
    }

    protected function applyReligionFilter(Builder $query, string $term): void
    {
        $like = '%' . mb_strtolower($term) . '%';

        $query->whereHas('religions', function ($q) use ($like, $term) {
            $q->leftJoin('religion_translations', 'religion_translations.religion_id', '=', 'religions.id')
                ->where(function ($religionQuery) use ($like, $term) {
                    $religionQuery->whereRaw('LOWER(religions.name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(religions.path_text) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(religions.lower_path_text) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(religion_translations.name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(religion_translations.path_text) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(religion_translations.lower_path_text) LIKE ?', [$like]);

                    if (ctype_digit($term)) {
                        $religionQuery->orWhere('religions.id', (int)$term);
                    }
                });
        });
    }

    protected function whereTranslatableNameLike(Builder $query, string $term): void
    {
        $like = '%' . mb_strtolower($term) . '%';

        $query->where(function ($q) use ($like) {
            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"cs\"'))) LIKE ?", [$like])
                ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) LIKE ?", [$like])
                ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
        });
    }

    protected function applyOrder(Builder $query): Builder
    {
        if (in_array($this->filters['order'] ?? '', ['name', 'birth_year', 'death_year'], true)) {
            return $query->orderBy($this->filters['order']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'type',
            'surname',
            'forename',
            'related_names',
            'nationality',
            'gender',
            'birth_year',
            'death_year',
            'professions',
            'categories',
            'viaf_id',
            'note',
            'admin_notes',
            'source'
        ];
    }

    public function map($identity): array
    {
        $professions = [];

        // Handle Local Professions (only exists on Local Identity)
        if ($identity->source_type === 'Local' && $identity->professions) {
            foreach ($identity->professions as $profession) {
                $name = $profession->getTranslation('name', config('hiko.metadata_default_locale'));
                $professions[] = "{$name} (Local)";
            }
        }

        // Handle Global Professions (exists on both Local and Global Identity)
        if ($identity->globalProfessions) {
            foreach ($identity->globalProfessions as $profession) {
                $name = $profession->getTranslation('name', config('hiko.metadata_default_locale'));
                $professions[] = "{$name} (Global)";
            }
        }

        // Handle Categories (Local only has access to profession_categories via relationship)
        $categories = [];
        if ($identity->source_type === 'Local' && $identity->profession_categories) {
            foreach ($identity->profession_categories as $category) {
                $categories[] = $category->getTranslation('name', config('hiko.metadata_default_locale'));
            }
        }

        return [
            $identity->id,
            $identity->name,
            $identity->type,
            $identity->surname,
            $identity->forename,
            $this->formatRelatedNames($identity->related_names),
            $identity->nationality,
            $identity->gender,
            $identity->birth_year,
            $identity->death_year,
            implode('|', $professions),
            implode('|', $categories),
            $identity->viaf_id,
            $identity->note,
            $identity->source_type === 'Global' ? $identity->admin_notes : '',
            $identity->source_type,
        ];
    }

    protected function formatRelatedNames($relatedNames): string
    {
        if (is_null($relatedNames)) {
            return '';
        }

        $relatedNamesArray = is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($relatedNamesArray)) {
            return '';
        }

        return implode(' | ', array_map(fn($name) =>
            trim("{$name['surname']} {$name['forename']} {$name['general_name_modifier']}"),
            $relatedNamesArray
        ));
    }
}
