<?php

namespace App\Http\Livewire;

use App\Models\Identity;
use Livewire\Component;
use Livewire\WithPagination;

class IdentitiesTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'surname',
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
        $query = Identity::select('id', 'surname', 'name', 'type', 'birth_year', 'death_year', 'alternative_names')
            ->with([
                'professions' => function ($subquery) {
                    $subquery->select('name')
                        ->orderBy('position');
                },
                'profession_categories' => function ($subquery) {
                    $subquery->select('name')
                        ->orderBy('position');
                },
            ]);

        $query->orderBy($this->filters['order']);

        return $query->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type'), __('hiko.dates'), __('hiko.alternative_names'), __('hiko.professions'), __('hiko.professions_category')],
            'rows' => $data->map(function ($identity) {
                return [
                    [
                        'label' => $identity->name,
                        'link' => route('identities.edit', $identity->id),
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
                        'label' => collect($identity->professions)->map(function ($profession) {
                            return implode('-', array_values($profession->getTranslations('name')));
                        })->toArray(),
                    ],
                    [
                        'label' => collect($identity->profession_categories)->map(function ($profession) {
                            return implode('-', array_values($profession->getTranslations('name')));
                        })->toArray(),
                    ],
                ];
            })->toArray(),
        ];
    }
}
