<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Identity;
use Illuminate\Support\Str;
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
        $query = Identity::select('id', 'name', 'type', 'birth_year', 'death_year', 'alternative_names')
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

        if (isset($this->filters['name']) && !empty($this->filters['name'])) {
            $query->where('name', 'LIKE', "%" . $this->filters['name'] . "%")
                ->orWhereRaw("LOWER(alternative_names) like ?", ["%" . Str::lower($this->filters['name']) . "%"]);
        }

        if (isset($this->filters['type']) && !empty($this->filters['type'])) {
            $query->where('type', '=', $this->filters['type']);
        }

        if (isset($this->filters['profession']) && !empty($this->filters['profession'])) {
            $query->whereHas('professions', function ($subquery) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($this->filters['profession']) . '%'])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($this->filters['profession']) . '%']);
            });
        }

        if (isset($this->filters['category']) && !empty($this->filters['category'])) {
            $query->whereHas('profession_categories', function ($subquery) {
                $subquery
                    ->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($this->filters['category']) . '%'])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($this->filters['category']) . '%']);
            });
        }

        $query->orderBy($this->filters['order']);

        return $query->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type'), __('hiko.dates'), __('hiko.alternative_names'), __('hiko.professions'), __('hiko.category')],
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
