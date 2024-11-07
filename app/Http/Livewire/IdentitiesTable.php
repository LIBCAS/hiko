<?php

namespace App\Http\Livewire;

use App\Models\Identity;
use App\Models\GlobalProfession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Stancl\Tenancy\Facades\Tenancy;

class IdentitiesTable extends Component
{
    use WithPagination;

    public array $filters = [
        'order' => 'name',
    ];

    public function search()
    {
        $this->resetPage();
        session()->put('identitiesTableFilters', $this->filters);
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function mount()
    {
        if (session()->has('identitiesTableFilters')) {
            $this->filters = session()->get('identitiesTableFilters');
        }
    }

    public function render()
    {
        $identities = $this->findIdentities();

        return view('livewire.identities-table', [
            'tableData' => $this->formatTableData($identities),
            'pagination' => $identities,
        ]);
    }

    protected function findIdentities()
    {
        $tenantPrefix = tenancy()->tenant ? tenancy()->tenant->table_prefix : null;
    
        // Fetch identities with local professions and profession categories
        $identities = Identity::with([
            'professions' => function ($subquery) use ($tenantPrefix) {
                $subquery->from("{$tenantPrefix}__professions")
                         ->select("{$tenantPrefix}__professions.id as profession_id", "{$tenantPrefix}__professions.name")
                         ->orderBy("{$tenantPrefix}__identity_profession.position");
            },
            'profession_categories' => function ($subquery) use ($tenantPrefix) {
                $subquery->from("{$tenantPrefix}__profession_categories")
                         ->select("{$tenantPrefix}__profession_categories.name")
                         ->orderBy("{$tenantPrefix}__identity_profession_category.position");
            },
        ])
        ->select('id', 'name', 'type', 'birth_year', 'death_year', 'related_names')
        ->search($this->filters)
        ->orderBy($this->filters['order'])
        ->paginate(10);
    
        // Prepare a mapping of global professions for each identity
        if ($tenantPrefix) {
            Tenancy::central(function () use ($identities, $tenantPrefix) {
                $identityIds = $identities->pluck('id');
    
                // Retrieve all global profession IDs for the fetched identities
                $globalProfessionsMapping = DB::table("{$tenantPrefix}__identity_profession")
                    ->whereIn('identity_id', $identityIds)
                    ->whereNotNull('global_profession_id')
                    ->pluck('global_profession_id', 'identity_id')
                    ->toArray();
    
                // Fetch global professions and map them to identities
                $globalProfessionIds = array_values($globalProfessionsMapping);
                $globalProfessions = GlobalProfession::whereIn('id', $globalProfessionIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');
    
                // Attach global professions to identities
                foreach ($identities as $identity) {
                    $globalProfessionId = $globalProfessionsMapping[$identity->id] ?? null;
                    $globalProfession = $globalProfessionId ? $globalProfessions->get($globalProfessionId) : null;
                    $identity->setRelation('globalProfessions', collect($globalProfession ? [$globalProfession] : []));
                }
            });
        }
    
        return $identities;
    }        

    protected function formatDates($identity): string
    {
        $birthYear = $identity->birth_year ?? '';
        $deathYear = $identity->death_year ?? '';

        return trim("{$birthYear} - {$deathYear}");
    }

    protected function formatRelatedNames($relatedNames): string
    {
        if (is_array($relatedNames)) {
            $formattedNames = array_map(function ($name) {
                return $name['surname'] . ' ' . $name['forename'] . ' ' . $name['general_name_modifier'];
            }, $relatedNames);
        } else {
            $relatedNamesArray = json_decode($relatedNames, true);
            if (is_array($relatedNamesArray)) {
                $formattedNames = array_map(function ($name) {
                    return $name['surname'] . ' ' . $name['forename'] . ' ' . $name['general_name_modifier'];
                }, $relatedNamesArray);
            } else {
                return '';
            }
        }

        return implode(', ', $formattedNames);
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type'), __('hiko.dates'), __('hiko.related_names'), __('hiko.professions'), __('hiko.category'), __('hiko.merge')],
            'rows' => $data->map(function ($identity) {
                $allProfessions = collect($identity->professions ?? [])
                    ->map(fn($profession) => [
                        'name' => ($profession->name ?? 'Unknown') . ' (Local)',
                        'link' => route('professions.edit', ['profession' => $profession->profession_id])
                    ])
                    ->merge(
                        collect($identity->globalProfessions ?? [])
                            ->map(fn($globalProfession) => [
                                'name' => ($globalProfession->name ?? 'Unknown') . ' (Global)',
                                'link' => route('global.professions.edit', ['globalProfession' => $globalProfession->id])
                            ])
                    );
    
                $professionsHtml = '<ul class="list-disc list-inside text-gray-600 space-y-1">';
                foreach ($allProfessions as $profession) {
                    $professionsHtml .= "<li><a href=\"{$profession['link']}\" class=\"text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark\">{$profession['name']}</a></li>";
                }
                $professionsHtml .= '</ul>';
    
                return [
                    [
                        'component' => [
                            'args' => [
                                'link' => route('identities.edit', ['identity' => $identity->identity_id ?? $identity->id]),
                                'label' => $identity->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ],
                    ['label' => __("hiko.{$identity->type}")],
                    ['label' => $this->formatDates($identity)],
                    ['label' => $this->formatRelatedNames($identity->related_names)],
                    ['label' => $professionsHtml, 'isHtml' => true],
                    [
                        'label' => collect($identity->profession_categories ?? [])
                            ->map(fn($category) => $category->name)
                            ->implode(', '),
                    ],
                    ['label' => $identity->alternative_names ?? ''],
                ];
            })->toArray(),
        ];
    }      
}
