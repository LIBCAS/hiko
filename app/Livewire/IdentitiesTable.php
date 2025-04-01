<?php

namespace App\Livewire;

use App\Models\Identity;
use App\Models\GlobalProfession;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Stancl\Tenancy\Facades\Tenancy;
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
    ];

    public function search()
    {
        $this->resetPage();
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
        ];
        $this->search();
    }

    public function mount()
    {
        // Load filters from session or set default values
        $this->filters = session()->get('identitiesTableFilters', [
            'name' => '',
            'related_names' => '',
            'type' => '',
            'profession' => '',
            'category' => '',
            'note' => '',
            'order' => 'name',
        ]);
    }

    public function render()
    {
        $identities = $this->findIdentities();

        return view('livewire.identities-table', [
            'tableData' => $this->formatTableData($identities),
            'pagination' => $identities,
        ]);
    }

    protected function formatDates($identity): string
    {
        return trim("{$identity->birth_year} - {$identity->death_year}");
    }

    protected function formatRelatedNames($relatedNames): string
    {
        if (is_null($relatedNames)) {
            return ''; // Returns an empty string if the relatedNames is equal to null
        }
    
        $relatedNamesArray = is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true);
    
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($relatedNamesArray)) {
            return ''; // Empty string in the case of the incorrect JSON
        }
    
        return implode(', ', array_map(fn($name) => 
            trim("{$name['surname']} {$name['forename']} {$name['general_name_modifier']}"),
            $relatedNamesArray
        ));
    }    

    protected function findIdentities(): LengthAwarePaginator
    {
        $filters = $this->filters;
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : null;
    
        $query = Identity::with([
            'professions.profession_category',
            'globalProfessions.profession_category',
        ])->select('id', 'name', 'type', 'birth_year', 'death_year', 'related_names');
    
        $query->when($filters['name'], fn($q) => $q->where('name', 'like', "%{$filters['name']}%"));
        $query->when($filters['related_names'], fn($q) => $q->where('related_names', 'like', "%{$filters['related_names']}%"));
        $query->when($filters['type'], fn($q) => $q->where('type', $filters['type']));
        $query->when($filters['profession'], fn($q) =>
            $q->whereHas('professions', fn($sq) =>
                $sq->where('name', 'like', "%{$filters['profession']}%")
            )
        );
    
        $query->when($filters['category'], fn($q) => $q->where(function ($sq) use ($filters, $tenantPrefix) {
            $sq->whereHas('professions.profession_category', fn($qq) =>
                $qq->where("{$tenantPrefix}__profession_categories.name", 'like', "%{$filters['category']}%")
            )->orWhereHas('globalProfessions.profession_category', fn($qq) =>
                $qq->where('name', 'like', "%{$filters['category']}%")
            );
        }));
    
        $query->when($filters['note'], fn($q) => $q->where('note', 'like', "%{$filters['note']}%"));
    
        if (in_array($filters['order'], ['name', 'birth_year', 'death_year'])) {
            $query->orderBy($filters['order']);
        }
    
        $identities = $query->paginate(25, ['*'], 'identitiesPage');
    
        // Enrich with global professions from central DB
        if ($tenantPrefix) {
            Tenancy::central(function () use ($identities, $tenantPrefix) {
                $ids = $identities->pluck('id');
                $mapping = DB::table("{$tenantPrefix}__identity_profession")
                    ->whereIn('identity_id', $ids)
                    ->whereNotNull('global_profession_id')
                    ->pluck('global_profession_id', 'identity_id');
    
                $globalProfessions = GlobalProfession::whereIn('id', $mapping->values())
                    ->with('profession_category')
                    ->get()
                    ->keyBy('id');
    
                foreach ($identities as $identity) {
                    $id = $mapping[$identity->id] ?? null;
                    $identity->setRelation('globalProfessions', collect($id ? [$globalProfessions[$id]] : []));
                }
            });
        }
    
        return $identities;
    }    

    protected function formatTableData($data): array
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type'), __('hiko.dates'), __('hiko.related_names'), __('hiko.professions') . ' | ' . __('hiko.attached_category')],
            'rows' => $data->map(function ($identity) {
                // Format professions with their attached categories
                $allProfessions = collect($identity->professions ?? [])
                    ->map(function ($profession) {
                        $category = $profession->profession_category;
                        return [
                            'name' => ($profession->name ?? 'Unknown') . ' (Local)',
                            'link' => route('professions.edit', ['profession' => $profession->id]),
                            'category' => $category ? $category->name : __('hiko.no_attached_category'),
                            'category_link' => $category ? route('professions.category.edit', $category->id) : null,
                        ];
                    })
                    ->merge(
                        collect($identity->globalProfessions ?? [])
                            ->map(function ($globalProfession) {
                                $category = $globalProfession->profession_category;
                                return [
                                    'name' => ($globalProfession->name ?? 'Unknown') . ' (Global)',
                                    'link' => route('global.professions.edit', ['globalProfession' => $globalProfession->id]),
                                    'category' => $category ? $category->name : __('hiko.no_attached_category'),
                                    'category_link' => $category ? route('global.professions.category.edit', $category->id) : null,
                                ];
                            })
                    );

                // Generate HTML for professions and categories
                $professionsHtml = '<ul class="list-disc list-inside text-gray-600 space-y-1">';
                foreach ($allProfessions as $profession) {
                    $professionsHtml .= "<li>";
                    $professionsHtml .= "<a href=\"{$profession['link']}\" class=\"text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark\">{$profession['name']}</a>";
                    if ($profession['category_link']) {
                        $professionsHtml .= " | <a href=\"{$profession['category_link']}\" class=\"text-xs text-primary-dark border-b border-primary-light hover:border-primary-dark\">{$profession['category']}</a>";
                    } else {
                        $professionsHtml .= " | <span class=\"text-xs text-gray-500\">{$profession['category']}</span>";
                    }
                    $professionsHtml .= "</li>";
                }
                $professionsHtml .= '</ul>';

                return [
                    [
                        'component' => [
                            'args' => [
                                'link' => route('identities.edit', ['identity' => $identity->id]),
                                'label' => $identity->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ],
                    ['label' => __("hiko.{$identity->type}")],
                    ['label' => $this->formatDates($identity)],
                    ['label' => $this->formatRelatedNames($identity->related_names)],
                    ['label' => $professionsHtml, 'isHtml' => true],
                ];
            })->toArray(),
        ];
    }
}
