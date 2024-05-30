<?php

namespace App\Http\Livewire;

use App\Models\Profession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ProfessionsTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
    ];

    public function search()
    {
        $this->resetPage('professionsPage');
    }

    public function render()
    {
        $professions = $this->findProfessions();

        return view('livewire.professions-table', [
            'tableData' => $this->formatTableData($professions),
            'pagination' => $professions,
        ]);
    }

    protected function findProfessions()
    {
        return Profession::with([
            'profession_category' => function ($subquery) {
                $subquery->select('id', 'name');
            },
        ])
            ->select('id', 'profession_category_id', 'name', DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"), DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en"))
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10, ['*'], 'professionsPage');
    }

    protected function formatTableData($data)
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? ['CS', 'EN', __('hiko.category')]
            : ['', 'CS', 'EN', __('hiko.category')];

        return [
            'header' => $header,
            'rows' => $data->map(function ($pf) {
                $row = auth()->user()->cannot('manage-metadata')
                    ? []
                    : [
                        [
                            'label' => __('hiko.edit'),
                            'link' => route('professions.edit', $pf->id),
                        ],
                    ];

                return array_merge($row, [
                    [
                        'label' => $pf->getTranslation('name', 'cs', false),
                    ],
                    [
                        'label' => $pf->getTranslation('name', 'en', false),
                    ],
                    [
                        'label' => $pf->profession_category ? $pf->profession_category->getTranslation('name', config('hiko.metadata_default_locale', false)) : '',
                    ],
                ]);
            })
                ->toArray(),
        ];
    }
}
