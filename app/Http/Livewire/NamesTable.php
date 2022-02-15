<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
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
        $query = app('App\Models\\' . $this->model)::select('id', 'name', DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"), DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en"));

        if (isset($this->filters['cs']) && !empty($this->filters['cs'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ['%' . Str::lower($this->filters['cs']) . '%']);
        }

        if (isset($this->filters['en']) && !empty($this->filters['en'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ['%' . Str::lower($this->filters['en']) . '%']);
        }

        $query->orderBy($this->filters['order']);

        return $query->paginate(10, ['*'], "{$this->model}Page");
    }

    protected function formatTableData($data)
    {
        return [
            'header' => ['', 'CS', 'EN'],
            'rows' => $data->map(function ($profession) {
                return [
                    [
                        'label' => __('hiko.edit'),
                        'link' => route("{$this->routePrefix}.edit", $profession->id),
                    ],
                    [
                        'label' => $profession->getTranslation('name', 'cs'),
                    ],
                    [
                        'label' => $profession->getTranslation('name', 'en'),
                    ],
                ];
            })->toArray(),
        ];
    }
}
