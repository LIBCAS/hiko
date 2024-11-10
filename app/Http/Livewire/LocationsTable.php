<?php

namespace App\Http\Livewire;

use App\Models\Location;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class LocationsTable extends Component
{
    use WithPagination;

    public array $filters = [
        'order' => 'name',
    ];

    public array $types;

    public function search()
    {
        $this->resetPage();
        session()->put('locationsTableFilters', $this->filters);
    }

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }
    public function mount()
    {
        if (session()->has('locationsTableFilters')) {
            $this->filters = session()->get('locationsTableFilters');
        }
    }

    public function render(): View
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
            ->paginate(25);
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
