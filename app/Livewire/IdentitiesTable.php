<?php

namespace App\Livewire;

use App\Models\Identity;
use App\Models\GlobalIdentity;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IdentitiesTable extends Component
{
    use WithPagination;

    public array $filters = [
        'name' => '',
        'related_names' => '',
        'type' => '',
        'profession' => '',
        'category' => '',
        'note' => '',
        'order' => 'name',
        'religion' => null,
        'source' => 'all',
        'global_identity' => '',
    ];

    public function search()
    {
        $this->resetPage('identitiesPage');
        session()->put('identitiesTableFilters', $this->filters);
    }

    public function resetFilters()
    {
        $this->filters = [
            'name' => '',
            'related_names' => '',
            'type' => '',
            'profession' => '',
            'category' => '',
            'note' => '',
            'order' => 'name',
            'religion' => null,
            'source' => 'all',
            'global_identity' => '',
        ];
        $this->search();
    }

    public function mount()
    {
        $this->filters = session()->get('identitiesTableFilters', $this->filters);
    }

    public function updatedFilters()
    {
        $this->search();
    }

    public function render()
    {
        $identities = $this->findIdentities();

        return view('livewire.identities-table', [
            'tableData' => $this->formatTableData($identities),
            'pagination' => $identities,
        ]);
    }

    protected function findIdentities(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $source = $filters['source'] ?? 'all';

        // Build Local Query
        $localQuery = null;
        if ($source === 'all' || $source === 'local') {
            if (tenancy()->initialized) {
                $prefix = tenancy()->tenant->table_prefix;
                $localQuery = DB::table("{$prefix}__identities")
                    ->leftJoin('global_identities', "{$prefix}__identities.global_identity_id", '=', 'global_identities.id')
                    ->select(
                        "{$prefix}__identities.id as id",
                        "{$prefix}__identities.name as name",
                        "{$prefix}__identities.type as type",
                        "{$prefix}__identities.birth_year as birth_year",
                        "{$prefix}__identities.death_year as death_year",
                        "{$prefix}__identities.related_names as related_names",
                        "{$prefix}__identities.global_identity_id as linked_global_identity_id",
                        "global_identities.name as linked_global_identity_name",
                        DB::raw("'local' as source")
                    );
                $this->applyFilters($localQuery, $filters, 'local');
            }
        }

        // Build Global Query
        $globalQuery = null;
        if ($source === 'all' || $source === 'global') {
            $globalQuery = DB::table('global_identities')
                ->select(
                    'id', 'name', 'type', 'birth_year', 'death_year', 'related_names',
                    DB::raw('NULL as linked_global_identity_id'),
                    DB::raw('NULL as linked_global_identity_name'),
                    DB::raw("'global' as source")
                );
            $this->applyFilters($globalQuery, $filters, 'global');
        }

        // Union and Paginate
        if ($localQuery && $globalQuery) {
            $query = $localQuery->unionAll($globalQuery);
        } elseif ($localQuery) {
            $query = $localQuery;
        } else {
            $query = $globalQuery;
        }

        if (!$query) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
        }

        $query->orderBy($filters['order']);
        $paginator = $query->paginate(25, ['*'], 'identitiesPage');

        // Hydrate Professions (Manual Eager Loading)
        $this->hydrateProfessions($paginator->getCollection());

        return $paginator;
    }

    protected function applyFilters($query, $filters, $scope)
    {
        $prefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : null;
        $nameColumn = $scope === 'local' ? "{$prefix}__identities.name" : 'global_identities.name';
        $relatedNamesColumn = $scope === 'local' ? "{$prefix}__identities.related_names" : 'global_identities.related_names';
        $typeColumn = $scope === 'local' ? "{$prefix}__identities.type" : 'global_identities.type';
        $noteColumn = $scope === 'local' ? "{$prefix}__identities.note" : 'global_identities.note';

        $query->when($filters['name'], fn($q) => $q->where($nameColumn, 'like', "%{$filters['name']}%"));
        $query->when($filters['related_names'], fn($q) => $q->where($relatedNamesColumn, 'like', "%{$filters['related_names']}%"));
        $query->when($filters['type'], fn($q) => $q->where($typeColumn, $filters['type']));
        $query->when($filters['note'], fn($q) => $q->where($noteColumn, 'like', "%{$filters['note']}%"));
        if (!empty($filters['global_identity'])) {
            $term = trim((string)$filters['global_identity']);
            if ($scope === 'local') {
                $prefix = tenancy()->tenant->table_prefix;
                $query->where(function ($q) use ($term, $prefix) {
                    $q->where('global_identities.name', 'like', '%' . $term . '%');

                    if (ctype_digit($term)) {
                        $q->orWhere("{$prefix}__identities.global_identity_id", (int)$term);
                    }
                });
            }
        }

        // Filtering by profession/category in a Union query is complex because tables differ.
        // Simplified approach: using EXISTS subqueries
        if ($filters['profession']) {
            if ($scope === 'local') {
                $prefix = tenancy()->tenant->table_prefix;
                $query->whereExists(function ($sub) use ($filters, $prefix) {
                    $sub->select(DB::raw(1))
                        ->from("{$prefix}__identity_profession")
                        ->join("{$prefix}__professions", "{$prefix}__identity_profession.profession_id", '=', "{$prefix}__professions.id")
                        ->whereColumn("{$prefix}__identity_profession.identity_id", "{$prefix}__identities.id")
                        ->where('name', 'like', '%' . $filters['profession'] . '%');
                });
            } else {
                $query->whereExists(function ($sub) use ($filters) {
                    $sub->select(DB::raw(1))
                        ->from('global_identity_profession')
                        ->join('global_professions', 'global_identity_profession.global_profession_id', '=', 'global_professions.id')
                        ->whereColumn('global_identity_profession.global_identity_id', 'global_identities.id')
                        ->where('name', 'like', '%' . $filters['profession'] . '%');
                });
            }
        }
    }

    /**
     * Manually attach professions to the plain object results from DB::table
     */
    protected function hydrateProfessions($items)
    {
        $localIds = $items->where('source', 'local')->pluck('id')->toArray();
        $globalIds = $items->where('source', 'global')->pluck('id')->toArray();

        // Fetch Local Professions
        $localProfessions = [];
        if (!empty($localIds)) {
            $localProfessions = Identity::with(['professions.profession_category', 'globalProfessions.profession_category'])
                ->whereIn('id', $localIds)
                ->get()
                ->keyBy('id');
        }

        // Fetch Global Professions
        $globalProfessionsMap = [];
        if (!empty($globalIds)) {
            $globalProfessionsMap = GlobalIdentity::with(['professions.profession_category'])
                ->whereIn('id', $globalIds)
                ->get()
                ->keyBy('id');
        }

        // Attach to items
        foreach ($items as $item) {
            $item->loaded_professions = collect();

            if ($item->source === 'local' && isset($localProfessions[$item->id])) {
                $identity = $localProfessions[$item->id];

                // Add Local Professions
                foreach ($identity->professions as $p) {
                    $item->loaded_professions->push([
                        'name' => $p->name,
                        'scope' => 'Local',
                        'link' => route('professions.edit', $p->id),
                        'category' => $p->profession_category->name ?? __('hiko.no_attached_category'),
                        'category_link' => $p->profession_category ? route('professions.category.edit', $p->profession_category->id) : null,
                    ]);
                }

                // Add Global Professions linked to Local Identity
                foreach ($identity->globalProfessions as $p) {
                    $item->loaded_professions->push([
                        'name' => $p->name,
                        'scope' => 'Global',
                        'link' => route('global.professions.edit', $p->id),
                        'category' => $p->profession_category->name ?? __('hiko.no_attached_category'),
                        'category_link' => $p->profession_category ? route('global.professions.category.edit', $p->profession_category->id) : null,
                    ]);
                }

            } elseif ($item->source === 'global' && isset($globalProfessionsMap[$item->id])) {
                $identity = $globalProfessionsMap[$item->id];

                foreach ($identity->professions as $p) {
                    $item->loaded_professions->push([
                        'name' => $p->name,
                        'scope' => 'Global',
                        'link' => route('global.professions.edit', $p->id),
                        'category' => $p->profession_category->name ?? __('hiko.no_attached_category'),
                        'category_link' => $p->profession_category ? route('global.professions.category.edit', $p->profession_category->id) : null,
                    ]);
                }
            }
        }
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => [
                __('hiko.name'),
                __('hiko.type'),
                __('hiko.dates'),
                __('hiko.related_names'),
                __('hiko.professions') . ' | ' . __('hiko.attached_category'),
                __('hiko.global_identity'),
                __('hiko.source')
            ],
            'rows' => $data->map(function ($identity) {
                $editRoute = $identity->source === 'global'
                    ? route('global.identities.edit', $identity->id)
                    : route('identities.edit', $identity->id);

                // Build Professions HTML
                $professionsHtml = '<ul class="list-disc list-inside text-gray-600 space-y-1">';
                if (isset($identity->loaded_professions)) {
                    foreach ($identity->loaded_professions as $p) {
                        $professionsHtml .= "<li>";
                        $professionsHtml .= "<a href=\"{$p['link']}\" class=\"text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark\">{$p['name']} ({$p['scope']})</a>";
                        if ($p['category_link']) {
                            $professionsHtml .= " | <a href=\"{$p['category_link']}\" class=\"text-xs text-primary-dark border-b border-primary-light hover:border-primary-dark\">{$p['category']}</a>";
                        } else {
                            $professionsHtml .= " | <span class=\"text-xs text-gray-500\">{$p['category']}</span>";
                        }
                        $professionsHtml .= "</li>";
                    }
                }
                $professionsHtml .= '</ul>';

                return [
                    [
                        'component' => [
                            'args' => [
                                'link' => $editRoute,
                                'label' => $identity->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ],
                    ['label' => __("hiko.{$identity->type}")],
                    ['label' => trim("{$identity->birth_year} - {$identity->death_year}")],
                    ['label' => $this->formatRelatedNames($identity->related_names)],
                    ['label' => $professionsHtml, 'isHtml' => true],
                    [
                        'label' => $this->formatGlobalIdentityCell($identity),
                        'isHtml' => true,
                    ],
                    [
                        'label' => $identity->source === 'global'
                            ? "<span class='inline-block bg-red-100 text-red-600 border border-red-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.global') . "</span>"
                            : "<span class='inline-block text-blue-600 bg-blue-100 border border-blue-200 text-xs uppercase px-2 py-1 rounded-full font-medium'>" . __('hiko.local') . "</span>",
                        'isHtml' => true
                    ],
                ];
            })->toArray(),
        ];
    }

    protected function formatRelatedNames($relatedNames): string
    {
        if (is_null($relatedNames)) return '';
        $relatedNamesArray = is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true);
        if (!is_array($relatedNamesArray)) return '';

        return implode(', ', array_map(
            fn($name) => trim("{$name['surname']} {$name['forename']} {$name['general_name_modifier']}"),
            $relatedNamesArray
        ));
    }

    protected function formatGlobalIdentityCell($identity): string
    {
        if ($identity->source !== 'local' || empty($identity->linked_global_identity_id)) {
            return '—';
        }

        $id = (int)$identity->linked_global_identity_id;
        $name = e($identity->linked_global_identity_name ?? ('#' . $id));
        $link = route('global.identities.edit', $id);

        return "<a href=\"{$link}\" class=\"text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark\">{$name}</a>";
    }
}
