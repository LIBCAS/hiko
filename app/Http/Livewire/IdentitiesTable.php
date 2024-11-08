<?php

namespace App\Http\Livewire;

use App\Models\Identity;
use App\Models\GlobalProfession;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Stancl\Tenancy\Facades\Tenancy;

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

    protected $queryString = ['filters'];

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
        if (session()->has('identitiesTableFilters')) {
            $this->filters = array_merge($this->filters, session()->get('identitiesTableFilters'));
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

        $query = Identity::with([
            'professions' => function ($subquery) use ($tenantPrefix) {
                $subquery->select("{$tenantPrefix}__professions.id as profession_id", "{$tenantPrefix}__professions.name")
                    ->orderBy("{$tenantPrefix}__identity_profession.position");
            },
            'profession_categories' => function ($subquery) use ($tenantPrefix) {
                $subquery->select("{$tenantPrefix}__profession_categories.name")
                    ->orderBy("{$tenantPrefix}__identity_profession_category.position");
            },
        ])
        ->select('id', 'name', 'type', 'birth_year', 'death_year', 'related_names');

        // Apply each filter conditionally
        $query->when($this->filters['name'], fn($q) => $q->where('name', 'like', '%' . $this->filters['name'] . '%'));
        $query->when($this->filters['related_names'], fn($q) => $q->where('related_names', 'like', '%' . $this->filters['related_names'] . '%'));
        $query->when($this->filters['type'], fn($q) => $q->where('type', $this->filters['type']));
        $query->when($this->filters['profession'], function ($q) use ($tenantPrefix) {
            $q->whereHas('professions', fn($subquery) => $subquery->where("{$tenantPrefix}__professions.name", 'like', '%' . $this->filters['profession'] . '%'));
        });
        $query->when($this->filters['category'], function ($q) use ($tenantPrefix) {
            $q->whereHas('profession_categories', fn($subquery) => $subquery->where("{$tenantPrefix}__profession_categories.name", 'like', '%' . $this->filters['category'] . '%'));
        });
        $query->when($this->filters['note'], fn($q) => $q->where('note', 'like', '%' . $this->filters['note'] . '%'));

        $identities = $query->orderBy($this->filters['order'])->paginate(10);

        if ($tenantPrefix) {
            Tenancy::central(function () use ($identities, $tenantPrefix) {
                $identityIds = $identities->pluck('id');
                $globalProfessionsMapping = DB::table("{$tenantPrefix}__identity_profession")
                    ->whereIn('identity_id', $identityIds)
                    ->whereNotNull('global_profession_id')
                    ->pluck('global_profession_id', 'identity_id')
                    ->toArray();

                $globalProfessionIds = array_values($globalProfessionsMapping);
                $globalProfessions = GlobalProfession::whereIn('id', $globalProfessionIds)
                    ->select('id', 'name')
                    ->get()
                    ->keyBy('id');

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
        return trim("{$identity->birth_year} - {$identity->death_year}");
    }

    protected function formatRelatedNames($relatedNames): string
    {
        $relatedNamesArray = is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true);

        return is_array($relatedNamesArray)
            ? implode(', ', array_map(fn($name) => "{$name['surname']} {$name['forename']} {$name['general_name_modifier']}", $relatedNamesArray))
            : '';
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type'), __('hiko.dates'), __('hiko.related_names'), __('hiko.professions'), __('hiko.category'), __('hiko.merge')],
            'rows' => $data->map(function ($identity) {
                $allProfessions = collect($identity->professions ?? [])
                    ->map(fn($profession) => [
                        'name' => ($profession->name ?? 'Unknown') . ' (Local)',
                        'link' => route('professions.edit', ['profession' => $profession->profession_id]),
                    ])
                    ->merge(
                        collect($identity->globalProfessions ?? [])
                            ->map(fn($globalProfession) => [
                                'name' => ($globalProfession->name ?? 'Unknown') . ' (Global)',
                                'link' => route('global.professions.edit', ['globalProfession' => $globalProfession->id]),
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
                    ['label' => $identity->profession_categories->pluck('name')->implode(', ')],
                    ['label' => $identity->alternative_names ?? ''],
                ];
            })->toArray(),
        ];
    }
}
