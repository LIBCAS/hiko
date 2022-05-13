<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Identity;
use Livewire\WithPagination;

class IdentitiesTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'name',
    ];

    public function search()
    {
        $this->resetPage();
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
            ->select('id', 'name', 'type', 'birth_year', 'death_year', 'alternative_names')
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type'), __('hiko.dates'), __('hiko.alternative_names'), __('hiko.professions'), __('hiko.category')],
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
                        'label' => $identity->dates,
                    ],
                    [
                        'label' => $identity->alternative_names,
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
                ];
            })->toArray(),
        ];
    }
}
