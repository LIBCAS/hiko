<?php

namespace App\Http\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use App\Models\Identity;
use Livewire\WithPagination;

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
    
    public function render(): View
    {
        $identities = $this->findIdentities();

        return view('livewire.identities-table', [
            'tableData' => $this->formatTableData($identities),
            'pagination' => $identities,
        ]);
    }

    protected function findIdentities()
    {
        return Identity::with([
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
            // If $relatedNames is already an array, use it directly
            $formattedNames = array_map(function ($name) {
                return $name['surname'] . ' ' . $name['forename'] . ' ' . $name['general_name_modifier'];
            }, $relatedNames);
        } else {
            // If $relatedNames is a string, decode it first
            $relatedNamesArray = json_decode($relatedNames, true);

            if (is_array($relatedNamesArray)) {
                $formattedNames = array_map(function ($name) {
                    return $name['surname'] . ' ' . $name['forename'] . ' ' . $name['general_name_modifier'];
                }, $relatedNamesArray);
            } else {
                // Handle the case where $relatedNames is neither an array nor a valid JSON string
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
                return [
                    [
                        'component' => [
                            'args' => [
                                'link' => route('identities.edit', $identity->id),
                                'label' => $identity->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ],
                    [
                        'label' => __("hiko.{$identity->type}"),
                    ],
                    [
                        'label' => $this->formatDates($identity),
                    ],
                    [
                        'label' => $this->formatRelatedNames($identity->related_names),
                    ],
                    [
                        'label' => collect($identity->professions)
                            ->map(function ($profession) {
                                return $profession->getTranslation('name', config('hiko.metadata_default_locale'), false);
                            })
                            ->toArray(),
                    ],
                    [
                        'label' => collect($identity->profession_categories)
                            ->map(function ($profession) {
                                return $profession->getTranslation('name', config('hiko.metadata_default_locale'), false);
                            })
                            ->toArray(),
                    ],
                    [
                        'label' => $identity->alternative_names
                    ],
                ];
            })->toArray(),
        ];
    }
}
