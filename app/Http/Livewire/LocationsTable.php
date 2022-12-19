<?php

namespace App\Http\Livewire;

use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class LocationsTable extends Component
{
    use WithPagination;

    public array $filters = [
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
        return Location::select('id', 'name', 'type')
            ->search($this->filters)
            ->orderBy($this->filters['order'])
            ->paginate(10);
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => [__('hiko.name'), __('hiko.type')],
            'rows' => $data->map(function ($locations) {
                return [
                    [
                        'component' => [
                            'args' => [
                                'link' => route('locations.edit', $locations->id),
                                'label' => $locations->name,
                            ],
                            'name' => 'tables.edit-link',
                        ],
                    ],
                    [
                        'label' => __("hiko.{$locations->type}"),
                    ],
                ];
            })
                ->toArray(),
        ];
    }
}
