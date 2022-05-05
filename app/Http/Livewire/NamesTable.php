<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class NamesTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
    ];

    public $model;

    public $routePrefix;

    public function search()
    {
        $this->resetPage("{$this->model}Page");
    }

    public function render()
    {
        $professions = $this->findProfessions();

        return view('livewire.names-table', [
            'tableData' => $this->formatTableData($professions),
            'pagination' => $professions,
        ]);
    }

    protected function findProfessions()
    {
        return app('App\Models\\' . $this->model)::select('id', 'name', DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"), DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en"))
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10, ['*'], "{$this->model}Page");
    }

    protected function formatTableData($data)
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? ['CS', 'EN']
            : ['', 'CS', 'EN'];

        return [
            'header' => $header,
            'rows' => $data->map(function ($profession) {
                $row = auth()->user()->cannot('manage-metadata')
                    ? []
                    : [
                        [
                            'label' => __('hiko.edit'),
                            'link' => route("{$this->routePrefix}.edit", $profession->id),
                        ],
                    ];

                return array_merge($row, [
                    [
                        'label' => $profession->getTranslation('name', 'cs'),
                    ],
                    [
                        'label' => $profession->getTranslation('name', 'en'),
                    ],
                ]);
            })
                ->toArray(),
        ];
    }
}
