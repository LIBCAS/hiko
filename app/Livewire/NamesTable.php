<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class NamesTable extends Component
{
    use WithPagination;

    public array $filters = ['order' => 'cs'];
    public string $model;
    public string $routePrefix;

    public function search()
    {
        $this->resetPage("{$this->model}Page");
        session()->put("{$this->model}TableFilters", $this->filters);
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function mount()
    {
        if (session()->has("{$this->model}TableFilters")) {
            $this->filters = session()->get("{$this->model}TableFilters");
        }
    }

    public function render()
    {
        $items = $this->findItems();

        return view('livewire.names-table', [
            'tableData' => $this->formatTableData($items),
            'pagination' => $items,
        ]);
    }

    protected function findItems()
    {
        $query = app('App\Models\\' . $this->model)::select(
            'id',
            'name',
            DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"),
            DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en")
        );
    
        // Implement search filters
        if (!empty($this->filters['cs'])) {
            $csFilter = strtolower($this->filters['cs']);
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%{$csFilter}%"]);
        }
    
        if (!empty($this->filters['en'])) {
            $enFilter = strtolower($this->filters['en']);
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$enFilter}%"]);
        }
    
        // Order by specified field
        $query->orderBy($this->filters['order']);
    
        // Paginate the results
        return $query->paginate(25, ['*'], "{$this->model}Page");
    }    

    protected function formatTableData($data): array
    {
        $header = auth()->user()->cannot('manage-metadata')
            ? ['CS', 'EN']
            : ['', 'CS', 'EN'];

        return [
            'header' => $header,
            'rows' => $data->map(function ($item) {
                $row = auth()->user()->cannot('manage-metadata')
                    ? []
                    : [
                        [
                            'label' => __('hiko.edit'),
                            'link' => route("{$this->routePrefix}.edit", $item->id),
                        ],
                    ];

                return array_merge($row, [
                    [
                        'label' => $item->getTranslation('name', 'cs', false),
                    ],
                    [
                        'label' => $item->getTranslation('name', 'en', false),
                    ],
                ]);
            })
                ->toArray(),
        ];
    }
}
