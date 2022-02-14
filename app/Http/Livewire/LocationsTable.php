<?php

namespace App\Http\Livewire;

use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class LocationsTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'name',
    ];

    public $types;

    public function search()
    {
        $this->resetPage();
    }

    public function render()
    {
        $locations = $this->findLocations();

        return view('livewire.locations-table', [
            'tableData' => $this->formatTableData($locations),
            'pagination' => $locations,
        ]);
    }

    protected function findLocations()
    {
        $query = Location::select('id', 'name', 'type');

        if (isset($this->filters['name']) && !empty($this->filters['name'])) {
            $query->where('name', 'LIKE', "%" . $this->filters['name'] . "%");
        }

        if (isset($this->filters['type']) && !empty($this->filters['type'])) {
            $query->where('type', '=', $this->filters['type']);
        }

        $query->orderBy($this->filters['order']);

        return $query->paginate(10);
    }

    protected function formatTableData($data)
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type')],
            'rows' => $data->map(function ($locations) {
                return [
                    [
                        'label' => $locations->name,
                        'link' => route('locations.edit', $locations->id),
                    ],
                    [
                        'label' => __("hiko.{$locations->type}"),
                    ],
                ];
            })->toArray(),
        ];
    }
}
