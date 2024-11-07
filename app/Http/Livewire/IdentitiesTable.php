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
        Log::info("Rendering IdentitiesTable with filters:", $this->filters);
        $identities = $this->findIdentities();

        return view('livewire.identities-table', [
            'tableData' => $this->formatTableData($identities),
            'pagination' => $identities,
        ]);
    }

    protected function findIdentities()
    {
        Log::info("Fetching identities with local professions and categories");

        // Fetch identities with local professions and categories, ensuring aliases for 'id' fields
        $identities = Identity::with([
            'professions' => function ($subquery) {
                $subquery->select('name')
                        ->orderBy('position');
            },

            'profession_categories' => function ($subquery) {
                $subquery->select('name')
                        ->orderBy('position');
            },
        ])
            ->select('id', 'name', 'type', 'birth_year', 'death_year', 'related_names')
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10);

        Log::info("Local identities fetched:", $identities->toArray());

        // Get tenant prefix if tenant is initialized
        $tenantPrefix = tenancy()->tenant ? tenancy()->tenant->table_prefix : null;

        // Fetch global professions from the central database if tenant is initialized
        if ($tenantPrefix) {
            Tenancy::central(function () use ($identities, $tenantPrefix) {
                $tenantPivotTable = "{$tenantPrefix}__identity_profession";

                foreach ($identities as $identity) {
                    $globalProfessionIds = DB::table($tenantPivotTable)
                        ->where('identity_id', $identity->identity_id)
                        ->whereNotNull('global_profession_id')
                        ->pluck('global_profession_id');

                    if ($globalProfessionIds->isNotEmpty()) {
                        $globalProfessions = GlobalProfession::whereIn('id', $globalProfessionIds)
                            ->select(DB::raw('global_professions.id as global_profession_id, name'))
                            ->get();

                        $identity->setRelation('globalProfessions', $globalProfessions);
                        Log::info("Global professions attached to identity {$identity->identity_id}:", $globalProfessions->toArray());
                    } else {
                        Log::info("No global professions found for identity {$identity->identity_id}");
                    }
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
        Log::info("Formatting table data for identities");

        return [
            'header' => [__('hiko.name'), __('hiko.type'), __('hiko.dates'), __('hiko.related_names'), __('hiko.professions'), __('hiko.category'), __('hiko.merge')],
            'rows' => $data->map(function ($identity) {
                // Combine local and global professions
                $allProfessions = collect($identity->professions)
                    ->map(fn($profession) => $profession->name . ' (Local)')
                    ->merge(
                        $identity->globalProfessions->map(fn($globalProfession) => $globalProfession->name . ' (Global)')
                    );

                Log::info("Combined professions for identity {$identity->identity_id}:", $allProfessions->toArray());

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
                    ['label' => $allProfessions->implode(', ')],
                    [
                        'label' => collect($identity->profession_categories)
                            ->map(fn($category) => $category->name)
                            ->implode(', '),
                    ],
                    ['label' => $identity->alternative_names ?? ''],
                ];
            })->toArray(),
        ];
    }      
}
